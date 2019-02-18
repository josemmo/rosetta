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

/**
 * An AbstractEntity is anything that can be found using the Search Engine.
 */
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


    /**
     * Remove relation
     * @param  Relation       $target Relation to remove
     * @return AbstractEntity         This instance
     */
    public function removeRelation(Relation $target): self {
        foreach ($this->relations as $i=>$relation) {
            if ($target === $relation) {
                array_splice($this->relations, $i, 1);
                break;
            }
        }
        return $this;
    }


    /**
     * Overwrite exiting relation of this type or create a new one if it doesn't exist
     * @param  Relation       $relation Relation
     * @return AbstractEntity           This instance
     */
    public function overwriteRelation(Relation $relation): self {
        // Replace existing relation
        foreach ($this->relations as $i=>$existingRelation) {
            if ($existingRelation->getType() == $relation->getType()) {
                $this->relations[$i] = $relation;
                return $this;
            }
        }

        // Create new relation if doesn't exist
        $this->addRelation($relation);
        return $this;
    }


    /**
     * Get related entities for given type
     * @param  int              $type Relation type
     * @return AbstractEntity[]       Abstract Entities
     */
    public function getRelated(int $type): array {
        $res = [];
        foreach ($this->relations as $relation) {
            if ($relation->getType() == $type) $res[] = $relation->getOther($this);
        }
        return $res;
    }


    /**
     * Get first related entity for given type
     * @param  int                 $type Relation type
     * @return AbstractEntity|null       Abstract Entity
     */
    public function getFirstRelated(int $type) {
        foreach ($this->relations as $relation) {
            if ($relation->getType() == $type) return $relation->getOther($this);
        }
        return null;
    }

}
