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

use Nicebooks\Isbn\Isbn;

class Edition extends AbstractWork {
    private $oclcNumber = null;
    private $isbn10 = null;
    private $isbn13 = null;
    private $volume = null;
    private $legalDeposit = null;
    private $pubDate = null;
    private $numOfPages = null;
    private $language = null;

    /**
     * Edition constructor
     * @param  string|null $isbn   ISBN 10 or ISBN 13
     * @param  int|null    $volume Volume number
     * @throws \Nicebooks\Isbn\Exception\InvalidIsbnException
     * @throws \Nicebooks\Isbn\Exception\IsbnNotConvertibleException
     */
    public function __construct(?string $isbn=null, ?int $volume=null) {
        $this->setIsbn($isbn);
        $this->setVolume($volume);
    }


    /**
     * Get OCLC number
     * @return string|null OCLC number
     */
    public function getOclcNumber(): ?string {
        return $this->oclcNumber;
    }


    /**
     * Set OCLC number
     * @param  string  $oclcNumber OCLC number
     * @return Edition             This instance
     */
    public function setOclcNumber(?string $oclcNumber): self {
        $this->oclcNumber = $oclcNumber;
        return $this;
    }


    /**
     * Get ISBN 10
     * @return string|null ISBN 10
     */
    public function getIsbn10(): ?string {
        return $this->isbn10;
    }


    /**
     * Get ISBN 13
     * @return string|null ISBN 13
     */
    public function getIsbn13(): ?string {
        return $this->isbn13;
    }

    /**
     * Set ISBN
     * @param  string|null  $isbn ISBN 10 or ISBN 13
     * @return Edition            This instance
     * @throws \Nicebooks\Isbn\Exception\InvalidIsbnException
     * @throws \Nicebooks\Isbn\Exception\IsbnNotConvertibleException
     */
    public function setIsbn(?string $isbn): self {
        if (is_null($isbn)) {
            $this->isbn10 = null;
            $this->isbn13 = null;
        } else {
            $isbnInstance = Isbn::of($isbn);
            $this->isbn10 = $isbnInstance->to10()->format();
            $this->isbn13 = $isbnInstance->to13()->format();
        }
        return $this;
    }


    /**
     * Get volume number
     * @return int|null Volume number
     */
    public function getVolume(): ?int {
        return $this->volume;
    }


    /**
     * Set volume number
     * @param  int|null $volume Volume number
     * @return Edition          This instance
     */
    public function setVolume(?int $volume): self {
        $this->volume = $volume;
        return $this;
    }


    /**
     * Get legal deposit
     * @return string|null Legal deposit
     */
    public function getLegalDeposit(): ?string {
        return $this->legalDeposit;
    }


    /**
     * Set legal deposit
     * @param  string|null $legalDeposit Legal deposit
     * @return Edition                   This instance
     */
    public function setLegalDeposit(?string $legalDeposit): self {
        $this->legalDeposit = $legalDeposit;
        return $this;
    }


    /**
     * Get publication date
     * @return \DateTime|null Publication date
     */
    public function getPubDate(): ?\DateTime {
        return $this->pubDate;
    }


    /**
     * Set publication date
     * @param  \DateTime|null $pubDate  Publication date
     * @return Edition                  This instance
     */
    public function setPubDate(?\DateTime $pubDate): self {
        $this->pubDate = $pubDate;
        return $this;
    }


    /**
     * Get number of pages
     * @return int|null Number of pages
     */
    public function getNumOfPages(): ?int {
        return $this->numOfPages;
    }


    /**
     * Set number of pages
     * @param  int|null $numOfPages Number of pages
     * @return Edition              This instance
     */
    public function setNumOfPages(?int $numOfPages): self {
        $this->numOfPages = $numOfPages;
        return $this;
    }


    /**
     * Get language
     * @return string|null Two-letter language code according to ISO 639-1
     */
    public function getLanguage(): ?string {
        return $this->language;
    }


    /**
     * Set language
     * @param  string|null $language Two-letter language code according to ISO 639-1
     * @return Edition               This instance
     */
    public function setLanguage(?string $language): self {
        $this->language = $language;
        return $this;
    }


    /**
     * Get publisher
     * @return Organization|null Publisher
     */
    public function getPublisher(): ?Organization {
        return $this->getFirstRelated(Relation::IS_PUBLISHER_OF);
    }


    /**
     * Set publisher
     * @param  Organization $publisher Publisher
     * @return Edition                 This instance
     */
    public function setPublisher(Organization $publisher): self {
        $this->overwriteRelation(new Relation($publisher, Relation::IS_PUBLISHER_OF, $this));
        return $this;
    }

}
