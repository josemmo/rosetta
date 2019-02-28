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

class Person extends AbstractEntity {
    private $firstname = null;
    private $lastname = null;
    private $description = null;
    private $birthDate = null;
    private $deathDate = null;

    /**
     * Get firstname
     * @return string|null Firstname
     */
    public function getFirstname(): ?string {
        return $this->firstname;
    }


    /**
     * Set firstname
     * @param  string $firstname Firstname
     * @return static            This instance
     */
    public function setFirstname(string $firstname): self {
        $this->firstname = $firstname;
        return $this;
    }


    /**
     * Get lastname
     * @return string|null Lastname
     */
    public function getLastname(): ?string {
        return $this->lastname;
    }


    /**
     * Set lastname
     * @param  string $lastname Lastname
     * @return static           This instance
     */
    public function setLastname(string $lastname): self {
        $this->lastname = $lastname;
        return $this;
    }


    /**
     * Get full name
     * @return string Full name
     */
    public function getName(): string {
        return implode(" ", array_filter([$this->firstname, $this->lastname]));
    }


    /**
     * Get description
     * @return string|null Description
     */
    public function getDescription(): ?string {
        return $this->description;
    }


    /**
     * Set description
     * @param  string|null $description Description
     * @return static                   This instance
     */
    public function setDescription(?string $description): self {
        $this->description = $description;
        return $this;
    }


    /**
     * Get birth date
     * @return \DateTime|null Birth date
     */
    public function getBirthDate(): ?\DateTime {
        return $this->birthDate;
    }


    /**
     * Set birth date
     * @param  \DateTime|null $birthDate Birth date
     * @return static                    This instance
     */
    public function setBirthDate(?\DateTime $birthDate): self {
        $this->birthDate = $birthDate;
        return $this;
    }


    /**
     * Get death date
     * @return \DateTime|null Death date
     */
    public function getDeathDate(): ?\DateTime {
        return $this->deathDate;
    }


    /**
     * Set death date
     * @param  \DateTime|null $deathDate Death date
     * @return static                    This instance
     */
    public function setDeathDate(?\DateTime $deathDate): self {
        $this->deathDate = $deathDate;
        return $this;
    }

}
