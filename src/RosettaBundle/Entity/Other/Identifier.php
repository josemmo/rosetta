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


namespace App\RosettaBundle\Entity\Other;

class Identifier {
    const INTERNAL = 1;
    const ISBN_10 = 2;
    const ISBN_13 = 3;
    const OCLC = 4;
    const GBOOKS = 5;

    private static $searchQueryFields = [
        self::ISBN_10 => "isbn",
        self::ISBN_13 => "isbn",
        self::OCLC => "oclc"
    ];

    private $type;
    private $id;

    /**
     * Identifier type to SearchQuery field
     * @param  int         $type Type
     * @return string|null       SearchQuery field
     */
    public static function toSearchQueryField(int $type): ?string {
        return self::$searchQueryFields[$type] ?? null;
    }


    /**
     * Identifier constructor
     * @param int    $type Type
     * @param string $id   ID
     */
    public function __construct(int $type, string $id) {
        $this->type = $type;
        $this->id = $id;
    }


    /**
     * Get type
     * @return int Type
     */
    public function getType(): int {
        return $this->type;
    }


    /**
     * Get ID
     * @return string ID
     */
    public function getId(): string {
        return $this->id;
    }


    /**
     * To string representation
     * @return string Identifier representation as text
     */
    public function __toString() {
        return "{" . $this->type . "}" . $this->id;
    }


    /**
     * To SearchQuery expression
     * @return string SearchQuery expression
     */
    public function toSearchQuery(): string {
        return self::toSearchQueryField($this->type) . ":" . $this->id;
    }

}
