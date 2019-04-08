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

    /** @ORM\Column(type="date", nullable=true) */
    private $lentUntil = null;

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
        if (!$loanable) $this->setLentUntil(null);
    }


    /**
     * Get lent until
     * @return \DateTime|null Lent until date
     */
    public function getLentUntil(): ?\DateTime {
        return $this->lentUntil;
    }


    /**
     * Set lent until date
     * @param \DateTime|null $lentUntil Lent until date (null if available)
     * @return static                   This instance
     */
    public function setLentUntil(?\DateTime $lentUntil): self {
        $this->lentUntil = $lentUntil;
        return $this;
    }

}
