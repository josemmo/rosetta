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

use App\RosettaBundle\Entity\Work\AbstractWork;
use App\RosettaBundle\Query\Comparison;

class Innopac extends AbstractHttpProvider {

    /**
     * @inheritdoc
     */
    public function prepare() {
        // Find ISBN in query
        $isbn = null;
        foreach ($this->query->getItems() as $item) {
            if (($item instanceof Comparison) && ($item->getField() == "isbn")) {
                $isbn = $item->getValue();
                break;
            }
        }

        // Enqueue ISBN request
        if (!is_null($isbn)) {
            $reqUrl = $this->config['url'];
            $reqUrl = str_replace('{{args}}', '?searchtype=i&searcharg=' . urlencode($isbn), $reqUrl);
            $this->enqueueRequest($this->newCurlRequest($reqUrl));
            return;
        }

        // Enqueue regular search
        $reqUrl = $this->config['url'];
        $args = "?searchtype=X&searcharg=" . urlencode($this->query->toInnopac());
        $reqUrl = str_replace('{{args}}', $args, $reqUrl);
        $this->enqueueRequest($this->newCurlRequest($reqUrl));
    }


    /**
     * Parse response
     * @param  string         $res HTML response
     * @return AbstractWork[]      Results
     */
    protected function parseResponse(string &$res) {
        $res = explode('<tr class="briefCiteRow">', $res);
        if (count($res) < 2) return [];

        // Parse results
        $results = [];
        for ($i=1; $i<count($res); $i++) {
            $result = $this->parseResult($res[$i]);
            if (!is_null($result)) $results[] = $result;
        }

        return $results;
    }


    /**
     * Parse result
     * @param  string            $res HTML response
     * @return AbstractWork|null      Result or null if failed to parse
     */
    private function parseResult(string &$res) {
        // TODO: not implemented
        return null;
    }

}
