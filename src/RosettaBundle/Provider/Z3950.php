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

use App\RosettaBundle\Entity\AbstractEntity;

class Z3950 extends AbstractProvider {
    private static $executed = false;

    private $config;
    private $conn;

    public function configure($config, $query) {
        self::$executed = false; // Reset flag for all Z39.50 instances
        $this->config = $config;

        $yazConfig = [];
        if (!is_null($config['user'])) $yazConfig['user'] = $config['user'];
        if (!is_null($config['group'])) $yazConfig['group'] = $config['group'];
        if (!is_null($config['password'])) $yazConfig['password'] = $config['password'];

        $conn = yaz_connect($config['url'], $yazConfig);
        yaz_syntax($conn, 'usmarc');
        yaz_range($conn, 1, $config['max_results']);
        yaz_search($conn, 'rpn', '@attr 1=4 "' . addslashes($query) . '"');
        $this->conn = $conn;
    }


    public function search() {
        if (self::$executed) return;

        $waitConfig = ['timeout' => 3];
        yaz_wait($waitConfig);

        self::$executed = true;
    }


    public function getResults(): array {
        $error = yaz_error($this->conn);
        if (!empty($error)) {
            $this->logger->error('Failed to get results from Z39.50 provider', [
                'url' => $this->config['url'],
                'yaz_error' => $error
            ]);
            return [];
        }

        // Parse results
        $results = [];
        $hits = yaz_hits($this->conn);
        for ($r=1; $r<=min($this->config['max_results'], $hits); $r++) {
            $result = yaz_record($this->conn, $r, 'array');
            $parsedResult = $this->parseResult($result);
            if (!empty($parsedResult)) $results[] = $parsedResult;
        }

        return $results;
    }


    /**
     * Parse MARC21 result
     * @param  array                $rawResult Raw result
     * @return AbstractEntity|false            Parsed result
     */
    private function parseResult($rawResult) {
        // TODO: not implemented
        return false;
    }

}
