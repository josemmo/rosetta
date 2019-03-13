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
        // Fetch results
        $localResults = $this->getLocalResults($query, $databases);
        $externalResults = $this->getExternalResults($localResults);
        $results = array_merge($localResults, $externalResults);
        unset($localResults);
        unset($externalResults);

        // Combine results
        $results = $this->groupResults($results);
        $results = $this->combineGroupedResults($results);
        return $results;
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
                $config = $db->getProvider();
                $config['id'] = $db->getId();
                $providersConfig[] = $config;
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


    /**
     * Group results that are the same
     * @param  AbstractEntity[]   $entities Array of entities
     * @return AbstractEntity[][]           Grouped entities
     */
    private function groupResults(array $entities): array {
        // Create table of identifiers
        $ids = [];
        foreach ($entities as $i=>$item) {
            foreach ($item->getIdentifiers() as $identifier) {
                if ($identifier->getType() == Identifier::ISBN_13) continue;
                $id = (string) $identifier;
                if (!isset($ids[$id])) $ids[$id] = [];
                $ids[$id][] = $i;
            }
        }

        // Sort IDs array by length (required for proper grouping)
        $ids = array_values($ids);
        usort($ids, function($a, $b) {
            return count($b) <=> count($a);
        });

        // Create groups
        $groups = [];
        $itemGroupPair = [];
        foreach ($ids as &$items) {
            // Find group ID
            $groupId = count($groups);
            foreach ($items as &$itemId) {
                if (isset($itemGroupPair[$itemId])) {
                    $groupId = $itemGroupPair[$itemId];
                    break;
                }
            }

            // Add items to group
            if (!isset($groups[$groupId])) $groups[$groupId] = [];
            foreach ($items as &$itemId) {
                if (!in_array($itemId, $groups[$groupId])) $groups[$groupId][] = $itemId;
                $itemGroupPair[$itemId] = $groupId;
            }
        }

        // Replace IDs with entities
        foreach ($groups as &$group) {
            foreach ($group as &$id) $id = $entities[$id];
        }

        return $groups;
    }


    /**
     * Combine grouped results
     * @param  AbstractEntity[][] $groupedEntities Grouped entities
     * @return AbstractEntity[]                    Array of entities
     */
    private function combineGroupedResults(array $groupedEntities): array {
        $res = [];

        foreach ($groupedEntities as $group) {
            $entity = $group[0];
            for ($i=1; $i<count($group); $i++) $entity->merge($group[$i]);
            $res[] = $entity;
        }

        return $res;
    }

}
