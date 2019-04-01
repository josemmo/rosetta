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
use App\RosettaBundle\Service\ConfigEngine;
use App\RosettaBundle\Utils\HttpClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CoversController extends AbstractController {
    private $em;
    private $config;

    public function __construct(EntityManagerInterface $em, ConfigEngine $config) {
        $this->em = $em;
        $this->config = $config;
    }


    /**
     * @Route("/images/covers/{id}.jpg", name="entity_cover")
     */
    public function getEntityCover(int $id) {
        // Get image URL
        $query = $this->em->createQuery('SELECT e.imageUrl FROM ' . AbstractEntity::class . ' e WHERE e.id=:id');
        $query->setParameter('id', $id);
        try {
            $imageUrl = $query->getSingleScalarResult();
        } catch (\Exception $e) {
            $imageUrl = null;
        }

        // Download image
        if (is_null($imageUrl)) {
            $imageData = base64_decode('data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        } else {
            $ch = HttpClient::newRequest($imageUrl);
            if (curl_errno($ch) == 0) $imageData = curl_exec($ch);
            curl_close($ch);
        }

        // Guess MIME type (can't trust server of origin)
        $finfo = new \finfo(FILEINFO_MIME);
        $mimeType = $finfo->buffer($imageData);
        $mimeType = explode(';', $mimeType)[0];

        // Send response
        $response = new Response(
            $imageData,
            Response::HTTP_OK,
            ['content-type' => $mimeType]
        );
        $expiration = new \DateTime($this->config->getOpacSettings()['covers_expiration']);
        $response->setExpires($expiration);
        return $response;
    }

}
