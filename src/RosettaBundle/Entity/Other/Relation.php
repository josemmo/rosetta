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

use App\RosettaBundle\Entity\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * A relation of a given type (reason) between two entities.
 * @ORM\Entity
 */
class Relation {
    const IS_AUTHOR_OF = 1;
    const IS_EDITOR_OF = 2;
    const IS_ILLUSTRATOR_OF = 3;
    const IS_PUBLISHER_OF = 10;
    const IS_FOUNDER_OF = 11;

    /**
     * @ORM\Id
     * @ORM\Column(type="smallint", options={"unsigned":true})
     */
    private $type;

    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="App\RosettaBundle\Entity\AbstractEntity", cascade={"persist", "remove"})
     */
    private $from;

    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="App\RosettaBundle\Entity\AbstractEntity", cascade={"persist", "remove"})
     */
    private $to;

    /**
     * Relation constructor
     * @param AbstractEntity $from Origin entity
     * @param int            $type Type of relation
     * @param AbstractEntity $to   Destination entity
     */
    public function __construct($from, int $type, $to) {
        $this->from = $from;
        $this->type = $type;
        $this->to = $to;
    }


    /**
     * Get type of relation
     * @return int Type of relation
     */
    public function getType(): int {
        return $this->type;
    }


    /**
     * Get origin entity
     * @return AbstractEntity Origin entity
     */
    public function getFrom() {
        return $this->from;
    }


    /**
     * Get destination entity
     * @return AbstractEntity Destination entity
     */
    public function getTo() {
        return $this->to;
    }


    /**
     * Get other than subject
     * Given an AbstractEntity, and assuming that subject appears
     * in this relation, returns the other AbstractEntity. Fallbacks to destination entity.
     * @param  $subject AbstractEntity Subject to exclude
     * @return          AbstractEntity Other entity of the relation
     */
    public function getOther($subject) {
        return ($subject === $this->getTo()) ? $this->getFrom() : $this->getTo();
    }


    /**
     * Overwrite other than subject
     * Same as `getOther`, but instead of a getter is a setter method.
     * @param  $subject  AbstractEntity Subject to exclude
     * @param  $newValue AbstractEntity New value for the entity
     * @return           static         This instance
     */
    public function overwriteOther($subject, $newValue) {
        if ($subject === $this->getTo()) {
            $this->from = $newValue;
        } else {
            $this->to = $newValue;
        }
        return $this;
    }

}
