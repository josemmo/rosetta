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
use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface {
    const ASSETS_DIR =  __DIR__ . "/../../assets/custom/";
    const ASSETS_URL = "build/images/";

    private $config;
    private $packages;
    private $assetsCache = [];

    /**
     * AppExtension constructor
     * @param ConfigEngine $config   Configuration Engine
     * @param Packages     $packages Packages Service
     */
    public function __construct(ConfigEngine $config, Packages $packages) {
        $this->config = $config;
        $this->packages = $packages;
        $this->buildAssetsCache();
    }


    /**
     * Build assets cache
     */
    private function buildAssetsCache() {
        $handle = opendir(self::ASSETS_DIR);
        if ($handle === false) return;

        while (false !== ($entry = readdir($handle))) {
            if ($entry == "." || $entry == ".." || $entry == ".gitignore") continue;
            $filename = pathinfo($entry, PATHINFO_FILENAME);
            $this->assetsCache[$filename] = self::ASSETS_URL . $entry;
        }

        closedir($handle);
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


    /**
     * @inheritdoc
     */
    public function getFunctions() {
        return [
            new TwigFunction('rosetta_asset', [$this, 'getRosettaAsset'])
        ];
    }


    /**
     * Get Rosetta custom asset path from name
     * @param  string      $tag Asset name (extension is optional)
     * @return string|null      Asset path or null if doesn't exist
     */
    public function getRosettaAsset(string $tag) {
        if (!isset($this->assetsCache[$tag])) return null;
        return $this->packages->getUrl($this->assetsCache[$tag]);
    }

}
