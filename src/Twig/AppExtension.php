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
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface {
    const ASSETS_DIR =  __DIR__ . "/../../assets/custom/";
    const ASSETS_URL = "build/images/";

    private $config;
    private $packages;
    private $request;
    private $assetsCache = [];

    /**
     * AppExtension constructor
     * @param ConfigEngine $config       Configuration Engine
     * @param Packages     $packages     Packages Service
     * @param RequestStack $requestStack Request Service
     */
    public function __construct(ConfigEngine $config, Packages $packages, RequestStack $requestStack) {
        $this->config = $config;
        $this->packages = $packages;
        $this->request = $requestStack->getCurrentRequest();
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
        // Get databases
        $databases = [];
        foreach ($this->config->getDatabases() as $db) {
            $databases[$db->getId()] = [
                "name" => $db->getName(),
                "short_name" => $db->getShortName()
            ];
        }

        // Get request context
        $dbId = $this->request->get('d');
        if (!isset($databases[$dbId])) $dbId = null;
        $context = [
            "db" => $dbId,
            "logo" => $this->getRosettaAsset("$dbId-logo", "logo"),
            "leading" => $this->getRosettaAsset("$dbId-leading", "leading"),
        ];

        return [
            "rosetta" => [
                "opac" => $this->config->getOpacSettings(),
                "databases" => $databases,
                "context" => $context
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
     * @param  string      $tag      Asset name (extension is optional)
     * @param  string|null $fallback Fallback asset name in case the first doesn't exist
     * @return string|null           Asset path or null if doesn't exist
     */
    public function getRosettaAsset(string $tag, string $fallback=null) {
        if (!isset($this->assetsCache[$tag])) {
            return is_null($fallback) ? null : $this->getRosettaAsset($fallback);
        }
        return $this->packages->getUrl($this->assetsCache[$tag]);
    }

}
