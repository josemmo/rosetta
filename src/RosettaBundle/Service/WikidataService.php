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
        if (!empty($queries)) {
            $results = $this->search($queries);
            foreach ($results as $query => $result) {
                $index = array_search($query, $queries);
                $entity = $entities[$indexes[$index]];
                $this->fillEntity($entity, $result);
            }
        }
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
        if (!empty($dataRes['entities'])) {
            foreach ($dataRes['entities'] as $id => &$entity) {
                $query = array_search($id, $wikidataIds);
                $results[$query] = $entity;
            }
        }

        return $results;
    }


    /**
     * Fill entity
     * @param AbstractEntity $entity Entity to fill
     * @param array          $data   Additional data from Wikidata
     */
    private function fillEntity($entity, $data) {
        // Add Wikidata ID
        $entity->addIdentifier(new Identifier(Identifier::WIKIDATA, $data['id']));

        // Add image URL
        $imageUrl = $this->parseProperty($data['claims'], 'P18');
        if (!is_null($imageUrl)) $entity->setImageUrl($imageUrl);

        // Get type of Wikidata instance
        $instanceOf = $this->parseProperty($data['claims'], 'P31');

        // Add Person properties
        if (($instanceOf == "Q5") && ($entity instanceof Person)) $this->fillPerson($entity, $data);
    }


    /**
     * Fill person
     * @param Person $entity Person to fill
     * @param array  $data   Additional data from Wikidata
     */
    private function fillPerson($entity, $data) {
        $description = null;
        foreach ($data['descriptions'] as $desc) {
            $description = $desc['value'];
            break;
        }
        if (!empty($description)) $entity->setDescription($description);

        $birthDate = $this->parseProperty($data['claims'], 'P569');
        if (!is_null($birthDate)) $entity->setBirthDate($birthDate);

        $deathDate = $this->parseProperty($data['claims'], 'P570');
        if (!is_null($deathDate)) $entity->setDeathDate($deathDate);

        $signatureUrl = $this->parseProperty($data['claims'], 'P109');
        if (!is_null($signatureUrl)) $entity->setSignatureUrl($signatureUrl);
    }


    /**
     * Parse Wikidata property
     * @param  array      $claims Wikidata entity claims array
     * @param  string     $propId Wikidata property ID
     * @return mixed|null         Parsed property or null if failed to parse
     */
    private function parseProperty($claims, $propId) {
        if (empty($claims[$propId])) return null;
        $property = $claims[$propId];

        try {
            $type = $property[0]['mainsnak']['datatype'];
            $value = $property[0]['mainsnak']['datavalue']['value'];
            if ($type == "string") return $value;
            if ($type == "time") return new \DateTime($value['time']);
            if ($type == "commonsMedia") {
                $value = str_replace(' ', '_', $value);
                return "https://commons.wikimedia.org/w/thumb.php?width=300&f=$value";
            }
            if ($type == "wikibase-item") return $value['id'];
        } catch (\Exception $e) {
            // Could not parse property, aborting
        }

        return null;
    }

}
