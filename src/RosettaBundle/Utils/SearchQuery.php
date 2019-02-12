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

class SearchQuery {
    private $title = null;
    private $author = null;
    private $isbn = null;

    /**
     * SearchQuery constructor.
     * @param string|array $query Raw query string or fields array
     */
    public function __construct($query) {
        if (is_string($query)) {
            $this->parseQueryString($query);
        } else {
            $fields = array_keys($this->toArray());
            foreach ($fields as $field) $this->{$field} = $query[$field];
        }
    }


    /**
     * Parse query string
     * @param string $query Query string
     */
    private function parseQueryString($query) {
        // TODO: not fully implemented
        $this->title = $query;
    }


    /**
     * Query to array
     * @return array Query fields
     */
    public function toArray(): array {
        return get_object_vars($this);
    }


    /**
     * Query to RPN syntax
     * @return string RPN query
     */
    public function toRpn(): string {
        // TODO: not fully implemented
        return '@attr 1=4 "' . addslashes($this->title) . '"';
    }

}
