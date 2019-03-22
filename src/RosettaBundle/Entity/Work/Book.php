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

use App\RosettaBundle\Entity\Organization;
use App\RosettaBundle\Entity\Other\Identifier;
use App\RosettaBundle\Entity\Other\Relation;
use Doctrine\ORM\Mapping as ORM;
use Nicebooks\Isbn\Isbn;

/**
 * A book is a written work composed of pages which can be divided across multiple volumes.
 * @ORM\Entity
 */
class Book extends AbstractWork {
    /** @ORM\Column(type="smallint", nullable=true, options={"unsigned":true}) */
    private $numOfPages = null;

    /** @ORM\Column(type="smallint", nullable=true, options={"unsigned":true}) */
    private $numOfVolumes = null;

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
     * @return static               This instance
     */
    public function setNumOfPages(?int $numOfPages): self {
        $this->numOfPages = $numOfPages;
        return $this;
    }


    /**
     * Get number of volumes
     * @return int|null Number of volumes
     */
    public function getNumOfVolumes(): ?int {
        return $this->numOfVolumes;
    }


    /**
     * Set number of volumes
     * @param  int|null $numOfVolumes Number of volumes
     * @return static                 This instance
     */
    public function setNumOfVolumes(?int $numOfVolumes): self {
        $this->numOfVolumes = $numOfVolumes;
        return $this;
    }


    /**
     * Get OCLC numbers
     * @return string[] OCLC numbers
     */
    public function getOclcNumbers(): ?string {
        return $this->getIdsOfType(Identifier::OCLC);
    }


    /**
     * Add OCLC number
     * @param  string $oclc OCLC number
     * @return static       This instance
     */
    public function addOclcNumber(string $oclc): self {
        $this->addIdentifier(new Identifier(Identifier::OCLC, $oclc));
        return $this;
    }


    /**
     * Get associated ISBN-10 codes
     * @return string[] ISBN-10 codes
     */
    public function getIsbn10s(): array {
        return $this->getIdsOfType(Identifier::ISBN_10);
    }


    /**
     * Get associated ISBN-13 codes
     * @return string[] ISBN-13 codes
     */
    public function getIsbn13s(): array {
        return $this->getIdsOfType(Identifier::ISBN_13);
    }


    /**
     * Add ISBN
     * NOTE: in case of invalid input no identifier will be assigned to the instance.
     * @param  string $isbn ISBN-10 or ISBN-13
     * @return static       This instance
     */
    public function addIsbn(string $isbn): self {
        try {
            $isbnInstance = Isbn::of($isbn);
            $isbn10 = str_replace('-', '', $isbnInstance->to10()->format());
            $isbn13 = str_replace('-', '', $isbnInstance->to13()->format());
            $this->addIdentifier(new Identifier(Identifier::ISBN_10, $isbn10));
            $this->addIdentifier(new Identifier(Identifier::ISBN_13, $isbn13));
        } catch (\Exception $e) {
            // Ignore ISBN if invalid
        }
        return $this;
    }


    /**
     * Get publisher
     * @return static|null Publisher
     */
    public function getPublisher() {
        return $this->getFirstRelatedOfType(Relation::IS_PUBLISHER_OF);
    }


    /**
     * Add publisher
     * @param  Organization $publisher Publisher
     * @return static                  This instance
     */
    public function addPublisher(Organization $publisher): self {
        $this->addRelation(new Relation($publisher, Relation::IS_PUBLISHER_OF, $this));
        return $this;
    }


    /**
     * @inheritdoc
     */
    public function merge($other) {
        // Number of pages
        if (!empty($other->getNumOfPages())) $this->setNumOfPages($other->getNumOfPages());

        // Number of volumes
        if (!empty($other->getNumOfVolumes())) $this->setNumOfVolumes($other->getNumOfVolumes());

        return parent::merge($other);
    }

}
