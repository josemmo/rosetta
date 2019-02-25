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


namespace App\Twig;

use App\RosettaBundle\Service\ConfigEngine;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface {
    private $config;

    /**
     * AppExtension constructor
     * @param ConfigEngine $config Configuration Engine
     */
    public function __construct(ConfigEngine $config) {
        $this->config = $config;
    }


    /**
     * @inheritdoc
     */
    public function getGlobals() {
        $institutions = [];
        foreach ($this->config->getInstitutions() as $institution) {
            $institutions[$institution->getId()] = [
                "name" => $institution->getName(),
                "short_name" => $institution->getShortName()
            ];
        }

        return [
            "rosetta" => [
                "opac" => $this->config->getOpacSettings(),
                "institutions" => $institutions
            ]
        ];
    }

}
