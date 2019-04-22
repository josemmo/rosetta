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

use App\RosettaBundle\Entity\Traits\HoldingsTrait;
use App\RosettaBundle\Utils\Normalizer;
use Doctrine\ORM\Mapping as ORM;

/**
 * A physical object that can be consulted or borrowed but is not consider a human work.
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Thing extends AbstractEntity {
    use HoldingsTrait;

    /** @ORM\Column(length=255) */
    private $name;

    /**
     * Get name
     * @return string Name
     */
    public function getName(): string {
        return $this->name;
    }


    /**
     * Set name
     * @param  string $name Name
     * @return static       This instance
     */
    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }


    /**
     * @inheritdoc
     */
    public function updateSlug(): self {
        $this->slug = Normalizer::normalizeSlug($this->name);
        return $this;
    }

}
