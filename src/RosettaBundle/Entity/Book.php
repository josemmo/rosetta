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

class Book extends AbstractEntity {
    private $title = null;
    private $subtitle = null;
    private $description = null;
    private $notes = [];
    private $editions = [];

    /**
     * Book constructor
     * @param string      $title    Title
     * @param string|null $subtitle Subtitle
     */
    public function __construct(?string $title=null, ?string $subtitle=null) {
        $this->setTitle($title);
        $this->setSubtitle($subtitle);
    }


    /**
     * Get title
     * @return string|null Title
     */
    public function getTitle() {
        return $this->title;
    }


    /**
     * Set title
     * @param  string|null $title Title
     * @return Book               This instance
     */
    public function setTitle(?string $title): self {
        $this->title = $title;
        return $this;
    }


    /**
     * Get subtitle
     * @return string|null Subtitle
     */
    public function getSubtitle(): ?string {
        return $this->subtitle;
    }


    /**
     * Set subtitle
     * @param  string|null $subtitle Subtitle
     * @return Book                  This instance
     */
    public function setSubtitle(?string $subtitle): self {
        $this->subtitle = $subtitle;
        return $this;
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
     * @return Book                     This instance
     */
    public function setDescription(?string $description): self {
        $this->description = $description;
        return $this;
    }


    /**
     * Get notes
     * @return string[] Note
     */
    public function getNotes(): array {
        return $this->notes;
    }


    /**
     * Set note
     * @param  string $note Note
     * @return Book         This instance
     */
    public function addNote(string $note): self {
        $this->notes[] = $note;
        return $this;
    }


    /**
     * Get editions
     * @return Edition[] Editions
     */
    public function getEditions(): array {
        return $this->editions;
    }


    /**
     * Add edition
     * @param  Edition $edition Edition
     * @return Book             This edition
     */
    public function addEdition(Edition $edition): Book {
        $this->editions[] = $edition;
        return $this;
    }


    /**
     * Get authors
     * @return Person[] Book authors
     */
    public function getAuthors(): array {
        return $this->getRelated(Relation::IS_AUTHOR_OF);
    }


    /**
     * Add author
     * @param  Person $author Author
     * @return Book           This instance
     */
    public function addAuthor(Person $author): self {
        $this->addRelation(new Relation($author, Relation::IS_AUTHOR_OF, $this));
        return $this;
    }

}
