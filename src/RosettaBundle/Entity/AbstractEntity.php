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


namespace App\RosettaBundle\Entity;

use App\RosettaBundle\Entity\Other\Identifier;
use App\RosettaBundle\Entity\Other\Relation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 * An AbstractEntity is anything that can be found using the Search Engine.
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="entity")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=5)
 * @ORM\DiscriminatorMap({
 *     "thing": "App\RosettaBundle\Entity\Thing",
 *     "work": "App\RosettaBundle\Entity\Work\AbstractWork",
 *     "book": "App\RosettaBundle\Entity\Work\Book",
 *     "org": "App\RosettaBundle\Entity\Organization",
 *     "pers": "App\RosettaBundle\Entity\Person"
 * })
 */
abstract class AbstractEntity {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="datetime") */
    protected $creationDate = null;

    /** @ORM\Column(type="datetime") */
    protected $modificationDate = null;

    /** @ORM\Column(length=300, options={"collation":"ascii_general_ci"}) */
    protected $slug = null;

    /** @ORM\Column(length=2083, nullable=true) */
    protected $imageUrl = null;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\RosettaBundle\Entity\Other\Identifier",
     *     indexBy="id",
     *     mappedBy="entity",
     *     fetch="EAGER",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $identifiers;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\RosettaBundle\Entity\Other\Relation",
     *     mappedBy="to",
     *     fetch="EXTRA_LAZY",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $relationsTo;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\RosettaBundle\Entity\Other\Relation",
     *     mappedBy="from",
     *     fetch="EXTRA_LAZY",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $relationsFrom;

    /**
     * AbstractEntity constructor
     */
    public function __construct() {
        $this->identifiers = new ArrayCollection();
        $this->relationsTo = new ArrayCollection();
        $this->relationsFrom = new ArrayCollection();
    }


    /**
     * Get entity ID
     * @return int|null Entity ID
     */
    public function getId(): ?int {
        return $this->id;
    }


    /**
     * Set entity ID
     * @param  int    $id Entity ID
     * @return static     This instance
     */
    public function setId(int $id): self {
        $this->id = $id;
        return $this;
    }


    /**
     * Get slug
     * @return string|null Slug
     */
    public function getSlug(): ?string {
        return $this->slug;
    }


    /**
     * Update slug
     * @return static This instance
     */
    public abstract function updateSlug();


    /**
     * Get creation date
     * @return \DateTime|null Creation date
     */
    public function getCreationDate(): ?\DateTime {
        return $this->creationDate;
    }


    /**
     * Set creation date
     * @param  \DateTime $date Creation date
     * @return static          This instance
     */
    public function setCreationDate(\DateTime $date): self {
        $this->creationDate = $date;
        return $this;
    }


    /**
     * Update creation date
     * @ORM\PrePersist
     * @return static This instance
     * @throws \Exception
     */
    public function updateCreationDate(): self {
        $this->creationDate = new \DateTime();
        return $this;
    }


    /**
     * Get modification date
     * @return \DateTime|null Modification date
     */
    public function getModificationDate(): ?\DateTime {
        return $this->modificationDate;
    }


    /**
     * Update modification date
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * @return static This instance
     * @throws \Exception
     */
    public function updateModificationDate(): self {
        $this->modificationDate = new \DateTime();
        return $this;
    }


    /**
     * Link identifiers to this entity
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * @return static This instance
     */
    public function linkIdentifiers(): self {
        foreach ($this->identifiers as $identifier) $identifier->setEntity($this);
        return $this;
    }


    /**
     * Get image URL
     * @return string|null Image URL
     */
    public function getImageUrl(): ?string {
        return $this->imageUrl;
    }


    /**
     * Set image URL
     * @param  string|null $imageUrl Image URL
     * @return static                This instance
     */
    public function setImageUrl(?string $imageUrl): self {
        $this->imageUrl = $imageUrl;
        return $this;
    }


    /**
     * Get identifiers
     * @return Collection<Identifier> Entity identifiers
     */
    public function getIdentifiers() {
        return $this->identifiers;
    }


    /**
     * Add identifier
     * @param  Identifier $identifier Identifier
     * @return static                 This instance
     */
    public function addIdentifier(Identifier $identifier): self {
        $key = (string) $identifier;
        $this->identifiers->set($key, $identifier);
        return $this;
    }


    /**
     * Add internal ID
     * @param  string $databaseId Database ID
     * @param  string $internalId Internal ID
     * @return static             This instance
     */
    public function addInternalId(string $databaseId, string $internalId): self {
        $this->addIdentifier(new Identifier(Identifier::INTERNAL, "$databaseId:$internalId"));
        return $this;
    }


    /**
     * Get entity IDs of given type
     * @param  int      $type Identifier type
     * @return string[]       Entity IDs
     */
    public function getIdsOfType(int $type): array {
        $res = [];
        foreach ($this->identifiers as $identifier) {
            if ($identifier->getType() == $type) $res[] = $identifier->getValue();
        }
        return $res;
    }


    /**
     * Get first entity ID of given type
     * @param  int         $type Identifier type
     * @return string|null       Entity ID or null if not found
     */
    public function getFirstIdOfType(int $type): ?string {
        foreach ($this->identifiers as $identifier) {
            if ($identifier->getType() == $type) return $identifier->getValue();
        }
        return null;
    }


    /**
     * Get entity relations
     * @return Collection<Relation> Entity relations
     */
    public function getRelations() {
        return new ArrayCollection(array_merge(
            $this->relationsTo->toArray(),
            $this->relationsFrom->toArray()
        ));
    }


    /**
     * Add relation
     * @param  Relation $relation Relation
     * @return static             This instance
     */
    public function addRelation(Relation $relation): self {
        if ($relation->getFrom() === $this) {
            $this->relationsFrom->add($relation);
        } else {
            $this->relationsTo->add($relation);
        }
        return $this;
    }


    /**
     * Remove duplicated relations
     * @ORM\PreFlush
     * @param PreFlushEventArgs $args Doctrine arguments
     */
    public function removeDuplicatedRelations(PreFlushEventArgs $args) {
        $unitOfWork = $args->getEntityManager()->getUnitOfWork();

        $cache = [];
        foreach ([$this->relationsTo, $this->relationsFrom] as $collection) {
            foreach ($collection as $i=>$relation) {
                $tag = (string) $relation;
                if (isset($cache[$tag])) {
                    $collection->remove($i);
                    $unitOfWork->detach($relation);
                } else {
                    $cache[$tag] = 1;
                }
            }
        }
    }


    /**
     * Get related entities of given type
     * @param  int      $type Relation type
     * @return static[]       Abstract Entities
     */
    public function getRelatedOfType(int $type): array {
        $res = [];
        foreach ($this->getRelations() as $relation) {
            if ($relation->getType() == $type) $res[] = $relation->getOther($this);
        }
        return $res;
    }


    /**
     * Get first related entity of given type
     * @param  int         $type Relation type
     * @return static|null       Abstract Entity
     */
    public function getFirstRelatedOfType(int $type) {
        foreach ($this->getRelations() as $relation) {
            if ($relation->getType() == $type) return $relation->getOther($this);
        }
        return null;
    }


    /**
     * Get entity type
     * @return string Entity type
     */
    public function getEntityType(): string {
        $type = explode('\\', static::class);
        $type = str_replace('Abstract', '', end($type));
        return strtolower($type);
    }


    /**
     * To filled template string
     * @param  string $template Template
     * @return string           Template filled with values from entity
     */
    public function toFilledTemplateString(string $template) {
        // ISBN 10
        if (strpos($template, '{{isbn10}}') !== false) {
            $value = $this->getFirstIdOfType(Identifier::ISBN_10);
            if (!is_null($value)) $template = str_replace('{{isbn10}}', $value, $template);
        }

        // ISBN 13
        if (strpos($template, '{{isbn13}}') !== false) {
            $value = $this->getFirstIdOfType(Identifier::ISBN_13);
            if (!is_null($value)) $template = str_replace('{{isbn13}}', $value, $template);
        }

        return $template;
    }


    /**
     * Get a summary string that identifies the entity's content
     * @return string|null Summary tag
     */
    public function getSummaryTag(): ?string {
        return null;
    }


    /**
     * Merge this entity with another one
     * @param  static $other Entity to merge with
     * @return static        This instance
     */
    public function merge($other) {
        // Image URL
        if (!is_null($other->getImageUrl())) $this->setImageUrl($other->getImageUrl());

        // Identifiers
        foreach ($other->getIdentifiers() as $identifier) $this->addIdentifier($identifier);

        return $this;
    }

}
