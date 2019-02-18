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

class Relation {
    // TODO: create type constants

    private $from;
    private $to;
    private $type;

    /**
     * Relation constructor
     * @param AbstractEntity $from Origin entity
     * @param AbstractEntity $to   Destination entity
     * @param int            $type Type of relation
     */
    public function __construct(AbstractEntity $from, AbstractEntity $to, int $type) {
        $this->from = $from;
        $this->to = $to;
        $this->type = $type;
    }


    /**
     * Get origin entity
     * @return AbstractEntity
     */
    public function getFrom(): AbstractEntity {
        return $this->from;
    }


    /**
     * Get destination entity
     * @return AbstractEntity
     */
    public function getTo(): AbstractEntity {
        return $this->to;
    }


    /**
     * Get type of relation
     * @return int
     */
    public function getType(): int {
        return $this->type;
    }

}
