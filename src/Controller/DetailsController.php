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


namespace App\Controller;

use App\RosettaBundle\Entity\AbstractEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DetailsController extends AbstractController {
    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }


    /**
     * @Route("/details/{id}", name="details_redirect")
     */
    public function fillInSlug(int $id) {
        // Get entity slug
        $query = $this->em->createQuery('SELECT e.slug FROM ' . AbstractEntity::class . ' e WHERE e.id=:id');
        $query->setParameter('id', $id);
        try {
            $slug = $query->getSingleScalarResult();
        } catch (\Exception $e) {
            $slug = null;
        }

        // Check entity exists
        if (empty($slug)) {
            throw $this->createNotFoundException('Cannot find entity with given ID');
        }

        // Redirect to full URL
        $params = ["id" => $id, "slug" => $slug];
        return $this->redirectToRoute('details', $params, 301);
    }


    /**
     * @Route("/details/{id}/{slug}", name="details")
     */
    public function viewItemDetails(int $id, string $slug) {
        // Get entity
        $query = $this->em->createQuery('SELECT e FROM ' . AbstractEntity::class . ' e WHERE e.id=:id');
        $query->setParameter('id', $id);
        try {
            $entity = $query->getSingleResult(); /** @var AbstractEntity $entity */
        } catch (\Exception $e) {
            $entity = null;
        }

        // Check entity exists
        if (empty($entity)) {
            throw $this->createNotFoundException('Cannot find entity with given ID');
        }

        // Fix URL slug if necessary
        if ($entity->getSlug() !== $slug) {
            $params = ["id" => $id, "slug" => $entity->getSlug()];
            return $this->redirectToRoute('details', $params, 301);
        }

        // Render page
        return $this->render("pages/details.html.twig", [
            "entity" => $entity
        ]);
    }

}
