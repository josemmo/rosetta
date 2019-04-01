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

use App\RosettaBundle\Entity\AbstractEntity;
use App\RosettaBundle\Entity\Other\Identifier;
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
        // Get databases
        $databases = [];
        foreach ($this->config->getDatabases() as $db) {
            $databases[$db->getId()] = [
                "name"       => $db->getName(),
                "short_name" => $db->getShortName()
            ];
        }

        // Get request context
        $db = $this->config->getCurrentDatabase();
        $dbId = empty($db) ? null : $db->getId();
        $context = [
            "db"      => $dbId,
            "logo"    => $this->getRosettaAsset("$dbId-logo", "logo"),
            "leading" => $this->getRosettaAsset("$dbId-leading", "leading"),
        ];

        return [
            "rosetta" => [
                "version"   => $this->config->getVersion(),
                "opac"      => $this->config->getOpacSettings(),
                "databases" => $databases,
                "context"   => $context
            ]
        ];
    }


    /**
     * @inheritdoc
     */
    public function getFunctions() {
        return [
            new TwigFunction('rosetta_asset', [$this, 'getRosettaAsset']),
            new TwigFunction('rosetta_external_links', [$this, 'getExternalLinks'])
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


    /**
     * Get external links from entity
     * @param  AbstractEntity $entity Entity
     * @return array                  External links
     */
    public function getExternalLinks($entity) {
        $res = [];
        foreach ($entity->getIdentifiers() as $identifier) {
            $value = $identifier->getValue();
            switch ($identifier->getType()) {
                case Identifier::GBOOKS:
                    $res[] = ['name' => 'Google Books', 'url' => "https://books.google.es/books?id=$value"];
                    break;
                case Identifier::OCLC:
                    $res[] = ['name' => 'WorldCat', 'url' => "https://www.worldcat.org/oclc/$value"];
                    break;
                case Identifier::WIKIDATA:
                    $res[] = ['name' => 'Wikidata', 'url' => "https://www.wikidata.org/entity/$value"];
                    break;
                case Identifier::INTERNAL:
                    $databaseId = explode(':', $value)[0];
                    $db = $this->config->getDatabases()[$databaseId];
                    $url = $db->getExternalLink();
                    if (!empty($url)) {
                        $url = $entity->toFilledTemplateString($url);
                        $res[] = ['name' => $db->getName(), 'url' => $url];
                    }
                    break;
            }
        }
        return $res;
    }

}
