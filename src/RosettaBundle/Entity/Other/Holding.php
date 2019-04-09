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
 * @ORM\Entity
 */
class Holding {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @ORM\GeneratedValue
     */
    private $id;

    /** @ORM\ManyToOne(targetEntity="App\RosettaBundle\Entity\Work\AbstractWork", inversedBy="holdings") */
    private $entity;

    /** @ORM\Column(length=64) */
    private $callNumber;

    /** @ORM\Column(type="boolean") */
    private $loanable = true;

    /** @ORM\Column(type="boolean") */
    private $available = true;

    /** @ORM\Column(length=16) */
    private $databaseId = null;

    /** @ORM\Column(length=128) */
    private $locationName = null;

    /** @ORM\Column(length=2083, nullable=true) */
    private $onlineUrl = null;

    /**
     * Holding constructor
     * @param string $callNumber Call number (pressmark)
     */
    public function __construct(string $callNumber) {
        $this->setCallNumber($callNumber);
    }


    /**
     * Get ID
     * @return int ID
     */
    public function getId(): int {
        return $this->id;
    }


    /**
     * Set ID
     * @param  int    $id Holding ID
     * @return static     This instance
     */
    public function setId(int $id): self {
        $this->id = $id;
        return $this;
    }


    /**
     * Get entity
     * @return AbstractEntity Entity
     */
    public function getEntity() {
        return $this->entity;
    }


    /**
     * Get entity
     * @param  AbstractEntity $entity Entity
     * @return static                 This instance
     */
    public function setEntity($entity) {
        $this->entity = $entity;
        return $this;
    }


    /**
     * Get call number
     * @return string Call number
     */
    public function getCallNumber(): string {
        return $this->callNumber;
    }


    /**
     * Set call number
     * @param  string  $callNumber Call number
     * @return static              This instance
     */
    public function setCallNumber(string $callNumber): self {
        $this->callNumber = $callNumber;
        return $this;
    }


    /**
     * Is loanable
     * @return boolean Is loanable
     */
    public function isLoanable(): bool {
        return $this->loanable;
    }


    /**
     * Set loanable flag
     * @param  boolean $loanable Is loanable
     * @return static            This instance
     */
    public function setLoanable(bool $loanable): self {
        $this->loanable = $loanable;
        return $this;
    }


    /**
     * Is available
     * @return boolean Is available
     */
    public function isAvailable(): bool {
        return $this->available;
    }


    /**
     * Set available flag
     * @param  boolean $available Is available
     * @return static             This instance
     */
    public function setAvailable(bool $available): self {
        $this->available = $available;
        return $this;
    }


    /**
     * Get database ID
     * @return string|null Database ID
     */
    public function getDatabaseId(): ?string {
        return $this->databaseId;
    }


    /**
     * Set database ID
     * @param  string $db Database ID
     * @return static     This instance
     */
    public function setDatabaseId(string $db): self {
        $this->databaseId = $db;
        return $this;
    }


    /**
     * Get location name
     * @return string|null Location name
     */
    public function getLocationName(): ?string {
        return $this->locationName;
    }


    /**
     * Set location name
     * @param  string $locationName Location name
     * @return static               This instance
     */
    public function setLocationName(string $locationName): self {
        $this->locationName = $locationName;
        return $this;
    }


    /**
     * Get online URL
     * @return string|null Online URL
     */
    public function getOnlineUrl(): ?string {
        return $this->onlineUrl;
    }


    /**
     * Set online URL
     * @param  string $onlineUrl Online URL
     * @return static            This instance
     */
    public function setOnlineUrl(string $onlineUrl): self {
        $this->onlineUrl = $onlineUrl;
        $this->setLoanable(false);
        return $this;
    }

}
