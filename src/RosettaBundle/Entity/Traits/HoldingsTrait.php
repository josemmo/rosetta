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

trait HoldingsTrait {
    // TODO: add ORM mapping
    protected $holdings = [];

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

}
