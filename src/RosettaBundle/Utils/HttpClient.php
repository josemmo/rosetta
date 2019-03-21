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


namespace App\RosettaBundle\Utils;

class HttpClient {
    private static $requests = [];
    private static $responses = [];

    /**
     * New cURL request
     * @param  string   $url     Request URL
     * @param  int      $timeout Request timeout in seconds
     * @return resource          cURL request
     */
    public static function newRequest(string $url, int $timeout=5) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Pragma: no-cache',
            'Cache-Control: no-cache',
            'User-Agent: rosetta',
            'Dnt: 1',
            'Accept-Encoding: gzip, deflate'
        ]);
        return $ch;
    }


    /**
     * Enqueue request
     * @param  resource $ch cURL request
     * @return int          Request ID
     */
    public static function enqueue($ch) {
        self::$requests[] = $ch;
        return count(self::$requests) - 1;
    }


    /**
     * Send all requests in queue
     */
    public static function sendQueue() {
        // Execute requests
        $mh = curl_multi_init();
        foreach (self::$requests as $ch) curl_multi_add_handle($mh, $ch);
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);

        // Save responses
        foreach (self::$requests as $i=>$ch) {
            self::$responses[$i] = curl_multi_getcontent($ch);
        }

        // Close handles
        foreach (self::$requests as $ch) {
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        self::$requests = [];
    }


    /**
     * Send single request
     * @param  resource $ch cURL request
     * @return string       Response
     */
    public static function sendSingleRequest($ch) {
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }


    /**
     * Get response
     * NOTE: once called, response will be cleared from memory.
     * @param  int         $id Request ID
     * @return string|null     Response
     */
    public static function getResponse($id) {
        $res = self::$responses[$id] ?? null;
        unset(self::$responses[$id]);
        return $res;
    }

}
