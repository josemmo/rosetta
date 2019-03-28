<?php
/**
 * Rosetta - A free (libre) Integrated Library System for the 21st century.
 * Copyright (C) 2019 José M. Moreno <josemmo@pm.me>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */


namespace App\RosettaBundle\Service;

use App\RosettaBundle\Entity\AbstractEntity;
use App\RosettaBundle\Entity\Other\Identifier;
use Doctrine\ORM\EntityManagerInterface;

class CacheService {
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }


    /**
     * Persist entities
     * @param AbstractEntity[] $entities Array of entities
     */
    public function persistEntities($entities) {
        $toPersist = [];

        // Create queue of all entities to analyze
        $queue = []; /** @var AbstractEntity[] $queue */
        foreach ($entities as $entity) {
            $queue[spl_object_hash($entity)] = $entity;
            foreach ($entity->getRelations() as $relation) {
                $other = $relation->getOther($entity);
                $queue[spl_object_hash($other)] = $other;
            }
        }
        $queue = array_values($queue);

        // Analyze items in queue for changes
        foreach ($queue as $i=>$entity) {
            $entity->updateSlug();

            // Prepare query
            if ($entity->getIdentifiers()->isEmpty()) {
                $query = $this->em->createQuery("SELECT e FROM " . get_class($entity) . " e
                                                      WHERE e.slug=:slug");
                $query->setParameter('slug', $entity->getSlug());
            } else {
                $ids = [];
                foreach ($entity->getIdentifiers() as $identifier) $ids[] = (string) $identifier;
                $query = $this->em->createQuery("SELECT e FROM " . Identifier::class . " i
                                                      JOIN " . AbstractEntity::class . " e WITH i.entity=e
                                                      WHERE i.id IN (:ids)");
                $query->setParameter('ids', $ids);
            }

            // Get entity from cache
            $cachedEntity = null; /** @var AbstractEntity|null $cachedEntity */
            try {
                $cachedEntity = $query->getOneOrNullResult();
            } catch (\Exception $e) {
                // Not a unique result, this should never happen
            }

            // Register changes
            if (is_null($cachedEntity)) {
                $toPersist[] = $entity;
            } else {
                // Copy old data from cached entity
                $entity->setId($cachedEntity->getId());
                $entity->setCreationDate($cachedEntity->getCreationDate());
                foreach ($cachedEntity->getIdentifiers() as $identifier) {
                    $entity->addIdentifier($identifier);
                }

                // Merge detached object
                $newEntity = $this->em->merge($entity);

                // Update references to old entity in other entities
                foreach ($entities as $target) {
                    if ($target === $entity) continue;
                    foreach ($target->getRelations() as $relation) {
                        if ($relation->getOther($target) === $entity) {
                            $relation->overwriteOther($target, $newEntity);
                        }
                    }
                }

                $entities[$i] = $entity;
            }
        }

        // Commit changes to database
        foreach ($toPersist as $entity) $this->em->persist($entity);
        $this->em->flush();
    }

}
