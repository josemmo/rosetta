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

use App\RosettaBundle\Entity\Other\Identifier;
use App\RosettaBundle\Entity\Other\Relation;
use Doctrine\ORM\Mapping as ORM;

/**
 * An AbstractEntity is anything that can be found using the Search Engine.
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="entity")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="entity_type", type="string", length=4)
 * @ORM\DiscriminatorMap({
 *     "work": "App\RosettaBundle\Entity\Work\AbstractWork",
 *     "book": "App\RosettaBundle\Entity\Work\Book",
 *     "org": "App\RosettaBundle\Entity\Organization",
 *     "pers": "App\RosettaBundle\Entity\Person"
 * })
 */
abstract class AbstractEntity {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned":true})
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="datetime") */
    protected $creationDate = null;

    /** @ORM\Column(type="datetime") */
    protected $modificationDate = null;

    /** @ORM\Column(length=300, options={"collation":"ascii_general_ci"}) */
    protected $slug = null;

    /** @ORM\Column(length=2083, nullable=true) */
    protected $imageUrl = null;

    /** @ORM\OneToMany(targetEntity="App\RosettaBundle\Entity\Other\Identifier", mappedBy="entity", cascade={"persist", "remove"}) */
    protected $identifiers = [];

    /** @ORM\OneToMany(targetEntity="App\RosettaBundle\Entity\Other\Relation", mappedBy="to", cascade={"persist", "remove"}) */
    protected $relations = [];

    /**
     * Get slug
     * @return string|null Slug
     */
    public function getSlug(): ?string {
        return $this->slug;
    }


    /**
     * Update slug
     * @ORM\PrePersist
     * @return static This instance
     */
    public abstract function updateSlug();


    /**
     * Get creation date
     * @return \DateTime|null Creation date
     */
    public function getCreationDate(): ?\DateTime {
        return $this->creationDate;
    }


    /**
     * Update creation date
     * @ORM\PrePersist
     * @return static This instance
     * @throws \Exception
     */
    public function updateCreationDate(): self {
        $this->creationDate = new \DateTime();
        return $this;
    }


    /**
     * Get modification date
     * @return \DateTime|null Modification date
     */
    public function getModificationDate(): ?\DateTime {
        return $this->modificationDate;
    }


    /**
     * Update modification date
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * @return static This instance
     * @throws \Exception
     */
    public function updateModificationDate(): self {
        $this->modificationDate = new \DateTime();
        return $this;
    }


    /**
     * Get image URL
     * @return string|null Image URL
     */
    public function getImageUrl(): ?string {
        return $this->imageUrl;
    }


    /**
     * Set image URL
     * @param  string|null $imageUrl Image URL
     * @return static                This instance
     */
    public function setImageUrl(?string $imageUrl): self {
        $this->imageUrl = $imageUrl;
        return $this;
    }


    /**
     * Get identifiers
     * @return Identifier[] Entity identifiers
     */
    public function getIdentifiers(): array {
        return array_values($this->identifiers);
    }


    /**
     * Add identifier
     * @param  Identifier $identifier Identifier
     * @return static                 This instance
     */
    public function addIdentifier(Identifier $identifier): self {
        $tag = (string) $identifier;
        if (!isset($this->identifiers[$tag])) $this->identifiers[$tag] = $identifier;
        return $this;
    }


    /**
     * Add internal ID
     * @param  string $databaseId Database ID
     * @param  string $internalId Internal ID
     * @return static             This instance
     */
    public function addInternalId(string $databaseId, string $internalId): self {
        $this->addIdentifier(new Identifier(Identifier::INTERNAL, "$databaseId:$internalId"));
        return $this;
    }


    /**
     * Get entity IDs of given type
     * @param  int      $type Identifier type
     * @return string[]       Entity IDs
     */
    public function getIdsOfType(int $type): array {
        $res = [];
        foreach ($this->identifiers as $identifier) {
            if ($identifier->getType() == $type) $res[] = $identifier->getValue();
        }
        return $res;
    }


    /**
     * Get entity relations
     * @return Relation[] Entity relations
     */
    public function getRelations(): array {
        return $this->relations;
    }


    /**
     * Add relation
     * @param  Relation $relation Relation
     * @return static             This instance
     */
    public function addRelation(Relation $relation): self {
        $this->relations[] = $relation;
        return $this;
    }


    /**
     * Get related entities of given type
     * @param  int      $type Relation type
     * @return static[]       Abstract Entities
     */
    public function getRelatedOfType(int $type): array {
        $res = [];
        foreach ($this->relations as $relation) {
            if ($relation->getType() == $type) $res[] = $relation->getOther($this);
        }
        return $res;
    }


    /**
     * Get first related entity of given type
     * @param  int         $type Relation type
     * @return static|null       Abstract Entity
     */
    public function getFirstRelatedOfType(int $type) {
        foreach ($this->relations as $relation) {
            if ($relation->getType() == $type) return $relation->getOther($this);
        }
        return null;
    }


    /**
     * Get entity type
     * @return string Entity type
     */
    public function getEntityType(): string {
        $type = explode('\\', static::class);
        $type = str_replace('Abstract', '', end($type));
        return strtolower($type);
    }


    /**
     * Get a summary string that identifies the entity's content
     * @return string|null Summary tag
     */
    public function getSummaryTag(): ?string {
        return null;
    }


    /**
     * Merge this entity with another one
     * @param  static $other Entity to merge with
     * @return static        This instance
     */
    public function merge($other) {
        // Image URL
        if (!is_null($other->getImageUrl())) $this->setImageUrl($other->getImageUrl());

        // Identifiers
        foreach ($other->getIdentifiers() as $identifier) $this->addIdentifier($identifier);

        return $this;
    }

}
