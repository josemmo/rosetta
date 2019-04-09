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


namespace App\RosettaBundle\Provider;

use App\RosettaBundle\Entity\Organization;
use App\RosettaBundle\Entity\Other\Holding;
use App\RosettaBundle\Entity\Other\Identifier;
use App\RosettaBundle\Entity\Other\Relation;
use App\RosettaBundle\Entity\Person;
use App\RosettaBundle\Entity\Work\Book;
use App\RosettaBundle\Query\SearchQuery;
use App\RosettaBundle\Utils\Normalizer;

class GoogleBooks extends AbstractHttpProvider {

    /**
     * @inheritdoc
     */
    public function prepare() {
        // Prepare URL queries
        $queries = [];
        foreach ($this->query->getItems() as $item) {
            if ($item instanceof SearchQuery) {
                $this->logger->warning('GoogleBooks provider does not allow for nested search queries');
                continue;
            }
            if ($item->getField() == "isbn") $queries[] = "isbn:" . $item->getValue();
        }

        // Create and enqueue requests
        $operand = $this->query->getOperand();
        foreach (array_chunk($queries, 30) as $queryChunk) {
            $urlQuery = implode(" $operand ", $queryChunk);
            $reqUrl = "https://www.googleapis.com/books/v1/volumes?q=" . urlencode($urlQuery);
            if (!empty($this->config['key'])) $reqUrl .= "&key=" . urlencode($this->config['key']);
            if (!empty($this->config['country'])) $reqUrl .= "&country=" . urlencode($this->config['country']);

            $ch = $this->newCurlRequest($reqUrl);
            $this->enqueueRequest($ch);
        }
    }


    /**
     * Parse response
     * @param  string $res HTML response
     * @return Book[]      Results
     */
    protected function parseResponse(string &$res) {
        $res = json_decode($res, true);
        if (empty($res['items'])) return [];

        $results = [];
        foreach ($res['items'] as &$data) {
            $item = new Book();

            // Set title
            $item->setTitle(Normalizer::normalizeTitle($data['volumeInfo']['title']));
            if (!empty($data['volumeInfo']['subtitle'])) {
                $item->setSubtitle(Normalizer::normalizeTitle($data['volumeInfo']['subtitle']));
            }

            // Add authors
            if (isset($data['volumeInfo']['authors'])) {
                foreach ($data['volumeInfo']['authors'] as $authorName) {
                    $person = new Person();
                    $person->setName(Normalizer::normalizeName($authorName));
                    $item->addRelation(new Relation($person, Relation::IS_AUTHOR_OF, $item));
                }
            }

            // Add publisher
            $publisher = $data['volumeInfo']['publisher'] ?? null;
            if (!empty($publisher)) {
                $organization = new Organization();
                $organization->setName(Normalizer::normalizeDefault($publisher));
                $item->addPublisher($organization);
            }

            // Add published date
            $pubDate = $data['volumeInfo']['publishedDate'] ?? null;
            if (!is_null($pubDate)) {
                $pubDate = explode('-', $pubDate);
                $pubDate = array_pad($pubDate, 3, null);
                $item->setPubDate($pubDate[0], $pubDate[1], $pubDate[2]);
            }

            // Add identifiers
            foreach ($data['volumeInfo']['industryIdentifiers'] as &$elem) {
                $item->addIsbn($elem['identifier']);
            }
            $item->addIdentifier(new Identifier(Identifier::GBOOKS, $data['id']));

            // Set page number
            if (isset($data['volumeInfo']['pageCount'])) {
                $item->setNumOfPages($data['volumeInfo']['pageCount']);
            }

            // Set cover URL
            $imageUrl = $data['volumeInfo']['imageLinks']['thumbnail'] ?? null;
            if (!empty($imageUrl)) $item->setImageUrl($imageUrl);

            // Set language
            if (isset($data['volumeInfo']['language'])) {
                $item->addLanguage($data['volumeInfo']['language']);
            }

            // Add holdings
            if ($this->config['get_holdings']) {
                $holding = $this->getHolding($data['saleInfo']);
                if (!is_null($holding)) $item->addHolding($holding);
            }

            $results[] = $item;
        }

        return $results;
    }


    /**
     * Parse holding
     * @param  array        $saleInfo Volume sale info
     * @return Holding|null           Holding
     */
    private function getHolding(&$saleInfo) {
        if (!$saleInfo['isEbook']) return null;
        if (!in_array($saleInfo['saleability'], ['FOR_SALE', 'FREE'])) return null;

        $holding = new Holding();
        $holding->setSourceId(Identifier::GBOOKS);
        $holding->setOnlineUrl($saleInfo['buyLink']);
        return $holding;
    }

}
