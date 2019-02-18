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

abstract class AbstractEntity {
    private $relations = [];

    /**
     * Get entity relations
     * @return Relation[] Entity relations
     */
    public function getRelations(): array {
        return $this->relations;
    }


    /**
     * Add relation
     * @param  Relation       $relation Relation
     * @return AbstractEntity           This instance
     */
    public function addRelation(Relation $relation): self {
        $this->relations[] = $relation;
        return $this;
    }

}
