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

/**
 * A database is a class representation of a source extracted from Rosetta configuration.
 */
class Database {
    private $id;
    private $name;
    private $shortName;
    private $externalLink;
    private $provider;

    /**
     * Database constructor
     * @param array $props Database properties
     */
    public function __construct(array $props) {
        $this->id = $props['id'];
        $this->name = $props['name'];
        $this->shortName = $props['short_name'];
        $this->externalLink = $props['external_link'];
        $this->provider = $props['provider'];
    }

    /**
     * Get ID
     * @return string ID
     */
    public function getId(): string {
        return $this->id;
    }


    /**
     * Get name
     * @return string Name
     */
    public function getName(): string {
        return $this->name;
    }


    /**
     * Get short name
     * @return string Short name
     */
    public function getShortName(): string {
        return $this->shortName;
    }


    /**
     * Get external link
     * @return string|null External link
     */
    public function getExternalLink(): ?string {
        return $this->externalLink;
    }


    /**
     * Get provider settings
     * @return array Provider settings
     */
    public function getProvider(): array {
        return $this->provider;
    }

}
