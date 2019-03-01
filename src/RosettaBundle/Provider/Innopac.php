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

class Innopac extends AbstractHttpProvider {

    /**
     * @inheritdoc
     */
    public function prepare() {
        // Prepare request URL
        $reqUrl = $this->config['url'];
        $reqUrl = str_replace('{{query}}', urlencode($this->query->toInnopac()), $reqUrl);

        // Create new cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $reqUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Pragma: no-cache',
            'Cache-Control: no-cache',
            'User-Agent: rosetta',
            'Dnt: 1',
            'Accept-Encoding: gzip, deflate'
        ]);

        // Add request to queue
        $this->enqueueRequest($ch);
    }


    /**
     * @inheritdoc
     */
    public function getResults(): array {
        $results = [];
        foreach ($this->responses as &$res) {
            array_merge($results, $this->parseResponse($res));
            unset($res);
        }
        return $results;
    }


    /**
     * Parse response
     * @param  string         $res HTML response
     * @return AbstractWork[]      Results
     */
    public function parseResponse(string &$res) {
        return []; // TODO: not implemented
    }

}
