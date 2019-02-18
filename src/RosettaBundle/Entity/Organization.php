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

class Organization extends AbstractEntity {
    private $name;
    private $foundationDate = null;
    private $website = null;

    /**
     * Get name
     * @return string Name
     */
    public function getName(): string {
        return $this->name;
    }


    /**
     * Set name
     * @param  string       $name Name
     * @return Organization       This instance
     */
    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }


    /**
     * Get foundation date
     * @return \DateTime|null Foundation date
     */
    public function getFoundationDate(): ?\DateTime {
        return $this->foundationDate;
    }


    /**
     * @param \DateTime|null $foundationDate Foundation date
     * @return Organization                  This instance
     */
    public function setFoundationDate(?\DateTime $foundationDate): self {
        $this->foundationDate = $foundationDate;
        return $this;
    }


    /**
     * Get website
     * @return string|null Website
     */
    public function getWebsite(): ?string {
        return $this->website;
    }


    /**
     * Set website
     * @param  string       $website Website
     * @return Organization          This instance
     */
    public function setWebsite(?string $website): self {
        $this->website = $website;
        return $this;
    }


    /**
     * Get founders
     * @return Person[] Founders
     */
    public function getFounders(): array {
        return $this->getRelated(Relation::IS_FOUNDER_OF);
    }


    /**
     * Add founder
     * @param  Person       $founder Founder
     * @return Organization          This instance
     */
    public function addFounder(Person $founder): self {
        $relation = new Relation($founder, Relation::IS_FOUNDER_OF, $this);
        $this->addRelation($relation);
        $founder->addRelation($relation);
        return $this;
    }

}
