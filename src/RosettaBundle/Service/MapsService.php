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

namespace App\RosettaBundle\Service;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpKernel\KernelInterface;

class MapsService {
    const MAP_XMLNS = "https://github.com/josemmo/rosetta";

    private $mapsDir;
    private $indexPath;
    private $cache;

    public function __construct(KernelInterface $kernel) {
        $this->mapsDir = $kernel->getProjectDir() . "/assets/custom/maps";
        $this->indexPath = $kernel->getCacheDir() . "/maps_index.php.cache";
        $this->cache = new ConfigCache($this->indexPath, true);
    }


    /**
     * Get maps index
     *
     * The maps index is a file contaning a summary of all data inside the maps directory. It summarizes into a single
     * file all location data to improve performance when trying to return map data for a particular holding.
     * This file is generated when new changed are made inside the maps directory.
     */
    private function getIndex() {
        if ($this->cache->isFresh()) return require $this->indexPath;
        $resources = [];
        $indexData = [];

        // List all maps in directory
        $mapNames = array_filter(scandir($this->mapsDir), function($elem) {
            return (substr($elem, -4) === ".svg");
        });

        // Parse map files
        foreach ($mapNames as $mapName) {
            $mapPath = $this->mapsDir . "/$mapName";
            $mapData = simplexml_load_file($mapPath);
            if (empty($mapData)) throw new Exception("Map '$mapName' is not a valid SVG file");

            // Add file to resources to watch
            $resources[] = new FileResource($mapPath);

            // Find namespace
            $ns = null;
            foreach ($mapData->getNamespaces() as $namespace=>$value) {
                if ($value === self::MAP_XMLNS) {
                    $ns = $namespace;
                    break;
                }
            }
            if (is_null($ns)) {
                throw new Exception("Missing namespace '" . self::MAP_XMLNS . "' from map '$mapName'");
            }

            // Get map attributes
            $attributes = $mapData->attributes($ns, true);
            if (empty($attributes['database'])) {
                throw new Exception("Missing '$ns:database' from map '$mapName'");
            }
            $database = strval($attributes['database']);
            $locationPattern = empty($attributes['locationPattern']) ? null : strval($attributes['locationPattern']);

            // Find all UDC codes appearing in this map
            $codes = $this->getUDCs($mapData, $namespace);
            $codes = array_unique($codes);

            // Add map to index
            if (!isset($indexData[$database])) $indexData[$database] = [];
            if (!isset($indexData[$database][$locationPattern])) $indexData[$database][$locationPattern] = [];
            foreach ($codes as $code) $indexData[$database][$locationPattern][$code] = $mapName;
        }

        // Cache index
        $this->cache->write('<?php return ' . var_export($indexData, true) . ';', $resources);
        return $indexData;
    }


    /**
     * Get UDC (Universal Decimal Classification) codes
     * @param  \SimpleXMLElement $xmlElement XML Element
     * @param  string            $ns         Rosetta namespace
     * @return string[]                      UDC codes
     */
    private function getUDCs(\SimpleXMLElement $xmlElement, string $ns) {
        $res = [];
        foreach ($xmlElement->children() as $child) {
            foreach ($this->getUDCs($child, $ns) as $code) $res[] = $code;
            $udc = $child->attributes($ns, true)->udc;
            if (empty($udc)) continue;

            // Parse UDC codes
            $codes = explode(',', $udc);
            foreach ($codes as $code) $res[] = trim($code);
        }
        return $res;
    }


    /**
     * Returns an SVG map with the location of the given resource, if exists
     * @param  string      $dbId       Database ID
     * @param  string      $callNumber Holding call number
     * @param  string|null $location   Holding location
     * @return string|null             Map name or null if not found
     */
    public function getMapName(string $dbId, string $callNumber, ?string $location=null) {
        $index = $this->getIndex();

        if (!isset($index[$dbId])) return null;

        // Find location
        $locationData = null;
        if (is_null($location)) {
            $locationData = $index[$dbId][''] ?? null;
        } else {
            foreach ($index[$dbId] as $locationPattern=>&$data) {
                if (preg_match("/$locationPattern/", $location) === 1) {
                    $locationData = $data;
                    break;
                }
            }
        }
        if (is_null($locationData)) return null;

        // Find UDC subject
        $udc = $this->extractUDC($callNumber);
        $mapName = null;
        while (strlen($udc) > 0) {
            if (isset($locationData[$udc])) {
                $mapName = $locationData[$udc];
                break;
            }
            $udc = substr($udc, 0, -1);
        }

        // Return map
        return $mapName;
    }


    /**
     * Extract UDC from signature
     * @param  string      $callNumber Holding call number
     * @return string|null             UDC subject code
     */
    private function extractUDC(string $callNumber): ?string {
        $matches = [];
        preg_match('/[0-9]{2,3}(\.[0-9]{1,3})?/', $callNumber, $matches);
        return empty($matches) ? null : $matches[0];
    }

}
