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


namespace App\RosettaBundle\Entity\Work;

use App\RosettaBundle\Entity\AbstractEntity;
use App\RosettaBundle\Entity\Other\Holding;
use App\RosettaBundle\Entity\Other\Relation;
use App\RosettaBundle\Entity\Person;

/**
 * An AbstractWork is a type of AbstractEntity that can be consulted or borrowed.
 */
abstract class AbstractWork extends AbstractEntity {
    private $title = null;
    private $legalDeposits = [];
    private $pubYear = null;
    private $pubMonth = null;
    private $pubDay = null;
    private $languages = [];
    private $holdings = [];

    /**
     * Set title
     * @return string|null Title
     */
    public function getTitle(): ?string {
        return $this->title;
    }


    /**
     * Set title
     * @param  string $title Title
     * @return static        This instance
     */
    public function setTitle(string $title): self {
        $this->title = $title;
        return $this;
    }


    /**
     * Get legal deposits
     * @return string[] Legal deposits
     */
    public function getLegalDeposit(): array {
        return $this->legalDeposits;
    }


    /**
     * Add legal deposit
     * @param  string $legalDeposit Legal deposit
     * @return static               This instance
     */
    public function addLegalDeposit(string $legalDeposit): self {
        $this->legalDeposits[] = $legalDeposit;
        return $this;
    }


    /**
     * Set publication date
     * @param  int      $year  Publication year
     * @param  int|null $month Publication month
     * @param  int|null $day   Publication day
     * @return static          This instance
     */
    public function setPubDate(int $year, int $month=null, int $day=null): self {
        $this->pubYear = $year;
        $this->pubMonth = $month;
        $this->pubDay = $day;
        return $this;
    }


    /**
     * Get languages
     * @return string[] Two-letter language codes according to ISO 639-1
     */
    public function getLanguages(): array {
        return $this->languages;
    }


    /**
     * Add language
     * @param  string $language Two-letter language code according to ISO 639-1
     * @return static           This instance
     */
    public function addLanguage(string $language): self {
        $this->languages[] = $language;
        return $this;
    }


    /**
     * Get work holdings
     * @return Holding[] Holdings
     */
    public function getHoldings(): array {
        return $this->holdings;
    }


    /**
     * Add holding
     * @param  Holding $holding Holding instance
     * @return static           This instance
     */
    public function addHolding(Holding $holding): self {
        $this->holdings[] = $holding;
        return $this;
    }


    /**
     * Get authors
     * @return Person[] Work authors
     */
    public function getAuthors(): array {
        return $this->getRelatedOfType(Relation::IS_AUTHOR_OF);
    }


    /**
     * Get main author (creator)
     * @return static|null Creator
     */
    public function getCreator() {
        return $this->getFirstRelatedOfType(Relation::IS_AUTHOR_OF);
    }

}
