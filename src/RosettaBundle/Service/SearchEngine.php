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
    private $wiki;
    private $cache;

    public function __construct(LoggerInterface $logger, ConfigEngine $config, WikidataService $wiki,
                                CacheService $cache) {
        $this->logger = $logger;
        $this->config = $config;
        $this->wiki = $wiki;
        $this->cache = $cache;
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

        // Enhance results
        $this->addMissingEntities($results);

        // Cache and return
        $this->cache->persistEntities($results);
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
                // Use ISBN-10 instead of both
                if ($identifier->getType() == Identifier::ISBN_13) continue;

                // Prevent duplicates
                $tag = (string) $identifier;
                if (isset($identifiers[$tag])) continue;

                // Check this identifier is searchable
                $identifierQuery = $identifier->toSearchQuery();
                if (!is_null($identifierQuery)) $identifiers[$tag] = $identifierQuery;
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
        foreach ($entities as $i=>$entity) {
            foreach ($entity->getIdentifiers() as $identifier) {
                if ($identifier->getType() == Identifier::ISBN_13) continue;
                $id = (string) $identifier;
                if (!isset($ids[$id])) $ids[$id] = [];
                $ids[$id][] = $i;
            }
        }

        // Group duplicated entities by repeated identifiers
        $ids = array_filter($ids, function($elem) {
            return count($elem) > 1;
        });
        $ids = array_values($ids);

        $parsedIds = [];
        while (!empty($ids)) {
            $indexes = array_shift($ids);
            $mustInsert = true;
            foreach ($ids as &$otherIndexes) {
                $areTheSameEntity = !empty(array_intersect($indexes, $otherIndexes));
                if ($areTheSameEntity) {
                    $mustInsert = false;
                    $otherIndexes = array_unique(array_merge($indexes, $otherIndexes));
                    sort($otherIndexes);
                }
            }
            if ($mustInsert) $parsedIds[] = $indexes;
        }

        // Populate groups array
        $groups = [];
        $alreadyInserted = [];
        foreach ($parsedIds as $indexes) {
            $group = [];
            foreach ($indexes as $index) {
                $group[] = $entities[$index];
                $alreadyInserted[$index] = 1;
            }
            $groups[] = $group;
        }
        foreach ($entities as $i=>$entity) {
            if (isset($alreadyInserted[$i])) continue;
            $groups[] = [$entity];
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


    /**
     * Merge related entities
     * @param AbstractEntity[] $entities Array of entities
     */
    private function mergeRelatedEntities(array $entities) {
        $related = [];
        foreach ($entities as $entity) {
            foreach ($entity->getRelations() as $relation) {
                $other = $relation->getOther($entity);
                $otherTag = $other->getSummaryTag();
                if (empty($otherTag)) continue;

                if (isset($related[$otherTag])) {
                    $related[$otherTag]->merge($other);
                    $relation->overwriteOther($entity, $related[$otherTag]);
                    unset($other);
                } else {
                    $related[$otherTag] = $other;
                }
            }
        }
    }


    /**
     * Add missing entities
     * @param AbstractEntity[] $entities Array of entities
     */
    private function addMissingEntities(array $entities) {
        // Merge duplicate entities before enhancing data
        $this->mergeRelatedEntities($entities);

        // Fill additional properties of related entities
        $related = [];
        foreach ($entities as $entity) {
            foreach ($entity->getRelations() as $relation) {
                $other = $relation->getOther($entity);
                $hash = spl_object_hash($other);
                if (!isset($related[$hash])) $related[$hash] = $other;
            }
        }
        $this->wiki->fillEntities($related);

        // Now that we have more data, merge one more time
        $this->mergeRelatedEntities($entities);
    }

}
