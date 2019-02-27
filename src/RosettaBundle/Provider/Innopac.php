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

use App\RosettaBundle\Entity\Database;
use App\RosettaBundle\Utils\SearchQuery;

class Innopac extends AbstractProvider {
    private static $executed = false;
    private static $requests = [];
    private static $instances = [];

    private $results = [];

    /**
     * @inheritdoc
     */
    public function configure(Database $database, SearchQuery $query) {
        self::$executed = false; // Reset flag for all INNOPAC instances

        // Prepare request URL
        $reqUrl = $database->getProvider()['url'];
        $reqUrl = str_replace('{{query}}', urlencode($query->toInnopac()), $reqUrl);

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
        self::$requests[] = $ch;
        self::$instances[] = $this;
    }


    /**
     * @inheritdoc
     */
    public function search() {
        if (self::$executed) return;

        // Execute requests
        $mh = curl_multi_init();
        foreach (self::$requests as $ch) curl_multi_add_handle($mh, $ch);
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        // Parse responses
        foreach (self::$requests as $i=>$ch) {
            $res = curl_multi_getcontent($ch);
            self::$instances[$i]->parseResponse($res);
        }

        // Close handles
        foreach (self::$requests as $ch) {
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        // Reset internal state
        self::$executed = true;
        self::$requests = [];
        self::$instances = [];
    }


    /**
     * @inheritdoc
     */
    public function getResults(): array {
        return $this->results;
    }


    /**
     * Parse response
     * @param string $res HTML response
     */
    public function parseResponse(string &$res) {
        // TODO: not implemented
    }

}
