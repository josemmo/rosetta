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

use App\RosettaBundle\Entity\Other\Database;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

class ConfigEngine {
    private $request;
    private $version;
    private $opac;
    private $wikidata;
    private $databases = [];
    private $externalProviders = [];

    /**
     * ConfigEngine constructor
     * @param RequestStack    $requestStack Request Stack Service
     * @param KernelInterface $kernel       Kernel Interface
     */
    public function __construct(RequestStack $requestStack, KernelInterface $kernel) {
        $this->request = $requestStack->getCurrentRequest();
        $this->version = trim(file_get_contents($kernel->getProjectDir() . "/VERSION"));
    }


    /**
     * Set configuration
     * @param array $config Configuration properties
     */
    public function setConfig($config) {
        // Save main settings
        $this->opac = $config['opac'];
        $this->wikidata = $config['wikidata'];

        // Create database instances
        foreach ($config['databases'] as $source) {
            $this->databases[$source['id']] = new Database($source);
        }

        // Save external providers
        $this->externalProviders = $config['external_providers'];
    }


    /**
     * Get app version
     * @return string App version
     */
    public function getVersion() {
        return $this->version;
    }


    /**
     * Get OPAC settings
     * @return array OPAC settings
     */
    public function getOpacSettings() {
        return $this->opac;
    }


    /**
     * Get Wikidata settings
     * @return array Wikidata settings
     */
    public function getWikidataSettings() {
        return $this->wikidata;
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


    /**
     * Get external providers
     * @return array External providers
     */
    public function getExternalProviders(): array {
        return $this->externalProviders;
    }

}
