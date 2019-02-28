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


namespace App\RosettaBundle\Entity;

class Identifier {
    const ISBN_10 = 1;
    const ISBN_13 = 2;
    const OCLC = 3;

    private $type;
    private $id;

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
     * @return string Type
     */
    public function getType(): string {
        return $this->type;
    }


    /**
     * Get ID
     * @return int ID
     */
    public function getId(): int {
        return $this->id;
    }
}
