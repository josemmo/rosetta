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
use App\RosettaBundle\Query\Operand;
use App\RosettaBundle\Query\SearchQuery;
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
     * @param  SearchQuery      $query     Search query
     * @param  string[]|null    $databases Database IDs to fetch results from, null for any
     * @return AbstractEntity[]            Search results
     */
    public function search(SearchQuery $query, ?array $databases=null) {
        $results = $this->getLocalResults($query, $databases);
        $externalResults = $this->getExternalResults($results);
        // TODO: clean results
        // TODO: merge results
        return array_merge($results, $externalResults);
    }


    /**
     * Get results from sources
     * @param  SearchQuery      $query           Search query
     * @param  array            $providersConfig Array of provider configurations
     * @return AbstractEntity[]                  Search results
     */
    private function getResultsFromProviders(SearchQuery $query, array $providersConfig) {
        // Instantiate and configure providers
        $providers = [];
        foreach ($providersConfig as $config) {
            $provider = new $config['type']($this->logger);
            $provider->configure($config, $query);
            $provider->prepare();
            $providers[] = $provider;
        }

        // Execute search
        foreach ($providers as $provider) $provider->execute();

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


    /**
     * Get results from local sources (databases)
     * @param  SearchQuery      $query     Search query
     * @param  string[]|null    $databases Database IDs to fetch results from, null for any
     * @return AbstractEntity[]            Search results
     */
    private function getLocalResults(SearchQuery $query, ?array $databases=null) {
        $providersConfig = [];
        foreach ($this->config->getDatabases() as $db) {
            if (empty($databases) || in_array($db->getId(), $databases)) {
                $providersConfig[] = $db->getProvider();
            }
        }

        return $this->getResultsFromProviders($query, $providersConfig);
    }


    /**
     * Get results from external sources
     * @param  AbstractEntity[] $entities Results from database providers
     * @return AbstractEntity[]           External results
     */
    private function getExternalResults(array $entities): array {
        $providersConfig = $this->config->getExternalProviders();
        if (empty($providersConfig)) return [];

        // Get identifiers from input entities
        $identifiers = [];
        foreach ($entities as $entity) {
            foreach ($entity->getIdentifiers() as $identifier) {
                if ($identifier->getType() == Identifier::ISBN_13) continue;
                $tag = (string) $identifier;
                if (!isset($identifiers[$tag])) $identifiers[$tag] = $identifier->toSearchQuery();
            }
        }
        if (empty($identifiers)) return [];

        // Prepare search query
        $query = [];
        foreach ($identifiers as $expression) {
            $query[] = $expression;
            $query[] = Operand::OR;
        }
        array_pop($query);
        $query = SearchQuery::of($query);

        // Get results
        return $this->getResultsFromProviders($query, $providersConfig);
    }

}
