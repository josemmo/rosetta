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

namespace App\RosettaBundle\Entity\Traits;

use App\RosettaBundle\Entity\Other\Holding;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * NOTE: for this trait to work, entities using it must have @ORM\HasLifecycleCallbacks.
 */
trait HoldingsTrait {
    /**
     * @ORM\OneToMany(
     *     targetEntity="App\RosettaBundle\Entity\Other\Holding",
     *     indexBy="id",
     *     mappedBy="entity",
     *     fetch="EAGER",
     *     cascade={"persist", "remove"}
     * )
     * @var ArrayCollection
     */
    protected $holdings = null;

    /**
     * Get work holdings
     * @return Collection<Holding> Holdings
     */
    public function getHoldings() {
        if (is_null($this->holdings)) $this->holdings = new ArrayCollection();
        return $this->holdings;
    }


    /**
     * Add holding
     * @param  Holding $holding Holding instance
     * @return static           This instance
     */
    public function addHolding(Holding $holding): self {
        if (is_null($this->holdings)) $this->holdings = new ArrayCollection();
        $this->holdings->add($holding);
        return $this;
    }


    /**
     * Link holdings to this entity
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * @return static This instance
     */
    public function linkHoldings(): self {
        if (!empty($this->holdings)) {
            foreach ($this->holdings as $holding) $holding->setEntity($this);
        }
        return $this;
    }

}
