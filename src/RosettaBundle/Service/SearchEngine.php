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
use App\RosettaBundle\Utils\SearchQuery;
use Psr\Log\LoggerInterface;

class SearchEngine {
    private $logger;
    private $config;

    public function __construct(LoggerInterface $logger, ConfigEngine $config) {
        $this->logger = $logger;
        $this->config = $config;
    }


    /**
     * Run new search
     * @param  SearchQuery      $query Search query
     * @return AbstractEntity[]        Search results
     */
    public function search(SearchQuery $query) {
        $results = $this->getResultsFromSources($query);
        // TODO: clean results
        // TODO: merge results
        return $results;
    }


    /**
     * Get results from sources
     * @param  SearchQuery      $query Search query
     * @return AbstractEntity[]        Search results
     */
    private function getResultsFromSources(SearchQuery $query) {
        // Instantiate and configure providers
        $providers = [];
        foreach ($this->config->getInstitutions() as $institution) {
            $providerType = $institution->getProvider()['type'];
            $provider = new $providerType($this->logger);
            $provider->configure($institution, $query);
            $providers[] = $provider;
        }

        // Execute search
        foreach ($providers as $provider) $provider->search();

        // Fetch search results
        $results = [];
        foreach ($providers as $provider) {
            $providerResults = $provider->getResults();
            $results = array_merge($results, $providerResults);
        }

        // Free memory
        foreach ($providers as $provider) unset($provider);

        return $results;
    }

}
