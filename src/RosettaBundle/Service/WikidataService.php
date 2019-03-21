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
use App\RosettaBundle\Entity\Person;
use App\RosettaBundle\Utils\HttpClient;

class WikidataService {
    private $config;

    public function __construct(ConfigEngine $config) {
        $this->config = $config->getWikidataSettings();
    }


    /**
     * Fill entities with Wikidata sources
     * @param AbstractEntity[] $entities Array of entities
     */
    public function fillEntities(array $entities) {
        // Extract query from entities
        $queries = [];
        $indexes = [];
        foreach ($entities as $i=>$entity) {
            if ($entity instanceof Person) {
                $queries[] = $entity->getName();
                $indexes[] = $i;
            }
        }

        // Get data from Wikidata and fill entities
        $results = $this->search($queries);
        foreach ($results as $query=>$result) {
            $index = array_search($query, $queries);
            $entity = $entities[$indexes[$index]];
            $this->fillEntity($entity, $result);
        }
    }


    /**
     * Fill entity
     * @param AbstractEntity $entity Entity to fill
     * @param array          $data   Additional data from Wikidata
     */
    private function fillEntity($entity, $data) {
        // TODO: not implemented
    }


    /**
     * Search Wikidata
     * @param  string[] $queries Search queries
     * @return array             Wikidata entities
     */
    private function search(array $queries) {
        // Create and send requests
        $receipts = [];
        foreach ($queries as $query) {
            $reqUrl = "https://www.wikidata.org/w/api.php?action=wbsearchentities";
            $reqUrl .= "&search=" . urlencode($query);
            $reqUrl .= "&language=" . urlencode($this->config['language']);
            $reqUrl .= "&format=json&props=";
            $receipts[] = HttpClient::enqueue(HttpClient::newRequest($reqUrl));
        }
        HttpClient::sendQueue();

        // Get Wikidata entity IDs from results
        $wikidataIds = [];
        foreach ($receipts as $i=>$reqId) {
            $res = HttpClient::getResponse($reqId);
            $res = json_decode($res, true);
            if (!is_null($res) && !empty($res['search'])) {
                $wikidataIds[$queries[$i]] = $res['search'][0]['id'];
            }
        }

        // Get entities data
        $dataUrl = "https://www.wikidata.org/w/api.php?action=wbgetentities";
        $dataUrl .= "&ids=" . urlencode(implode('|', $wikidataIds));
        $dataUrl .= "&languages=" . urlencode($this->config['language']);
        $dataUrl .= "&format=json";
        $dataRes = HttpClient::sendSingleRequest(HttpClient::newRequest($dataUrl));
        $dataRes = json_decode($dataRes, true);

        // Parse results
        $results = [];
        foreach ($dataRes['entities'] as $id=>&$entity) {
            $query = array_search($id, $wikidataIds);
            $results[$query] = $entity;
        }

        return $results;
    }

}
