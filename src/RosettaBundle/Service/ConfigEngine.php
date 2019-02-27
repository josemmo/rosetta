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

use App\RosettaBundle\Entity\Database;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfigEngine {
    private $request;
    private $opac;
    private $databases = [];

    /**
     * ConfigEngine constructor
     * @param RequestStack $requestStack Request Stack Service
     */
    public function __construct(RequestStack $requestStack) {
        $this->request = $requestStack->getCurrentRequest();
    }


    /**
     * Set configuration
     * @param array $config Configuration properties
     */
    public function setConfig($config) {
        // Save OPAC settings
        $this->opac = $config['opac'];

        // Create database instances
        foreach ($config['databases'] as $source) {
            $this->databases[$source['id']] = new Database($source);
        }
    }


    /**
     * Get OPAC settings
     * @return array OPAC settings
     */
    public function getOpacSettings() {
        return $this->opac;
    }


    /**
     * Get current database from context
     * @return Database|null Database instance or null for all catalog
     */
    public function getCurrentDatabase(): ?Database {
        $dbId = $this->request->get('d');
        return $this->databases[$dbId] ?? null;
    }


    /**
     * Get databases
     * @return Database[] Databases
     */
    public function getDatabases(): array {
        return $this->databases;
    }

}
