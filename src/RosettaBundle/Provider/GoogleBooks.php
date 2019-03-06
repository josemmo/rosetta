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

use App\RosettaBundle\Entity\Work\Book;
use App\RosettaBundle\Query\SearchQuery;

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
        return []; // TODO: not implemented
    }

}
