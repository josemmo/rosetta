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
use App\RosettaBundle\Entity\Other\Holding;
use App\RosettaBundle\Entity\Other\Identifier;
use App\RosettaBundle\Entity\Other\Map;
use App\RosettaBundle\Service\ConfigEngine;
use App\RosettaBundle\Service\MapsService;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension implements GlobalsInterface {
    const ASSETS_DIR =  __DIR__ . "/../../assets/custom/";
    const ASSETS_URL = "build/images/";

    private $config;
    private $maps;
    private $packages;
    private $router;
    private $translator;
    private $assetsCache = [];

    /**
     * AppExtension constructor
     * @param ConfigEngine    $config     Configuration Engine
     * @param MapsService     $maps       Maps Service
     * @param Packages        $packages   Packages Service
     * @param RouterInterface $router     Router Service
     * @param mixed           $translator Translator Service
     */
    public function __construct(ConfigEngine $config, MapsService $maps, Packages $packages,
                                RouterInterface $router, $translator) {
        $this->config = $config;
        $this->maps = $maps;
        $this->packages = $packages;
        $this->router = $router;
        $this->translator = $translator;
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
            new TwigFunction('rosetta_entity_path', [$this, 'getEntityPath']),
            new TwigFunction('rosetta_source_name', [$this, 'getSourceName']),
            new TwigFunction('rosetta_external_links', [$this, 'getExternalLinks']),
            new TwigFunction('rosetta_date', [$this, 'getFormattedDate']),
            new TwigFunction('rosetta_language', [$this, 'getLanguageName']),
            new TwigFunction('rosetta_get_map', [$this, 'getMapFromHolding'])
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
     * Get path to entity
     * @param  AbstractEntity $entity Entity
     * @return string                 Entity path
     */
    public function getEntityPath($entity) {
        return $this->router->generate('details', [
            "id" => $entity->getId(),
            "slug" => $entity->getSlug()
        ]);
    }


    /**
     * Get source name
     * @param  int|string  $sourceId  Identifier type ID or database ID
     * @param  boolean     $shortName Get short name
     * @return string|null            Source name
     */
    public function getSourceName($sourceId, bool $shortName=false) {
        // Identifier type
        if (is_int($sourceId)) {
            if ($sourceId == Identifier::GBOOKS) return "Google Books";
            if ($sourceId == Identifier::OCLC) return "WorldCat";
            if ($sourceId == Identifier::WIKIDATA) return "Wikidata";
            return null;
        }

        // Database ID
        $db = $this->config->getDatabases()[$sourceId] ?? null;
        if (is_null($db)) return null;
        return $shortName ? $db->getShortName() : $db->getName();
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
            $type = $identifier->getType();
            $sourceName = $this->getSourceName($type);
            switch ($type) {
                case Identifier::GBOOKS:
                    $res['gbooks'] = ['name' => $sourceName, 'url' => "https://books.google.es/books?id=$value"];
                    break;
                case Identifier::OCLC:
                    $res['oclc'] = ['name' => $sourceName, 'url' => "https://www.worldcat.org/oclc/$value"];
                    break;
                case Identifier::WIKIDATA:
                    $res['wikidata'] = ['name' => $sourceName, 'url' => "https://www.wikidata.org/entity/$value"];
                    break;
                case Identifier::INTERNAL:
                    $databaseId = explode(':', $value)[0];
                    $db = $this->config->getDatabases()[$databaseId];
                    $url = $db->getExternalLink();
                    if (!empty($url)) {
                        $url = $entity->toFilledTemplateString($url);
                        if (!is_null($url)) $res["internal-$databaseId"] = ['name' => $db->getName(), 'url' => $url];
                    }
                    break;
            }
        }
        return array_values($res);
    }


    /**
     * Get month name from number
     * @param  int    $month Month number
     * @return string        Localized month name
     */
    private function getMonthName($month) {
        $formatter = new \IntlDateFormatter(
            $locale = $this->translator->getLocale(),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            'MMMM'
        );
        return $formatter->format($month);
    }


    /**
     * Get formatted date
     * @param  \DateTime|int[] $date DateTime instance or [YYYY, MM, DD] array
     * @return string                Formatted date
     * @throws \Exception
     */
    public function getFormattedDate($date) {
        // In case of stepped date
        if (is_array($date)) {
            if (empty($date[1])) return strval($date[0]);
            if (empty($date[2])) {
                return $this->translator->trans('%month%, %year%', [
                    "%month%" => $this->getMonthName($date[1]),
                    "%year%" => $date[0]
                ]);
            }
            $date = new \DateTime(implode('-', $date) . " 00:00:00");
        }

        // Does this date have time?
        $hasTime = ($date->format('H:i:s') != "00:00:00");

        // Is a recent date?
        if ($hasTime) {
            $diff = abs(time() - $date->getTimestamp());
            if ($diff < 60) return $this->translator->trans('Just now');
            if ($diff < 3600) {
                return $this->translator->trans('%minutes% minutes ago', [
                    '%minutes%' => floor($diff / 60)
                ]);
            }
            if ($diff < 43200) {
                return $this->translator->trans('%hours% hours ago', [
                    '%hours%' => floor($diff / 3600)
                ]);
            }
        }

        // Fallback to default
        $timeFormat = $hasTime ? \IntlDateFormatter::SHORT : \IntlDateFormatter::NONE;
        $locale = $locale = $this->translator->getLocale();
        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::LONG, $timeFormat);
        return $formatter->format($date);
    }


    /**
     * Get language name
     * @param  string $code Two-letter language code according to ISO 639-1
     * @return string       Localized language name
     */
    public function getLanguageName(string $code) {
        return \Locale::getDisplayLanguage($code, $this->translator->getLocale());
    }


    /**
     * Get map from holding
     * @param  Holding  $holding Holding
     * @return Map|null          Map
     */
    public function getMapFromHolding(Holding $holding) {
        return $this->maps->getMap($holding);
    }

}
