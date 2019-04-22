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

class Map {
    private $id;
    private $data;
    private $room;

    public function __construct(int $id, string $data, string $room) {
        $this->id = $id;
        $this->data = $data;
        $this->room = $room;
    }


    /**
     * Get identifier
     * @return int ID
     */
    public function getId(): int {
        return $this->id;
    }


    /**
     * Get map SVG data
     * @return string Data
     */
    public function getData(): string {
        return $this->data;
    }


    /**
     * Get room name
     * @return string Room
     */
    public function getRoom(): string {
        return $this->room;
    }
}
