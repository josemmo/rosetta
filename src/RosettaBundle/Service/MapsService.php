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

use App\RosettaBundle\Entity\Other\Holding;
use App\RosettaBundle\Entity\Other\Map;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\HttpKernel\KernelInterface;

class MapsService {
    const MAP_XMLNS = "https://github.com/josemmo/rosetta";

    private $mapsDir;
    private $indexPath;
    private $cache;
    private $cachedMaps = [];

    public function __construct(KernelInterface $kernel) {
        $this->mapsDir = $kernel->getProjectDir() . "/assets/custom/maps";
        $this->indexPath = $kernel->getCacheDir() . "/maps_index.php.cache";
        $this->cache = new ConfigCache($this->indexPath, true);
    }


    /**
     * Get map instance from holding
     * @param  Holding  $holding Holding
     * @return Map|null          Map
     */
    public function getMap(Holding $holding) {
        $mapName = $this->getMapName($holding->getSourceId(), $holding->getSubject(), $holding->getLocationName());
        if (is_null($mapName)) return null;

        // Get map instance from cache
        if (isset($this->cachedMaps[$mapName])) return $this->cachedMaps[$mapName];

        // Load map into memory
        $map = $this->loadMap($mapName);
        $this->cachedMaps[$mapName] = $map;

        return $map;
    }


    /**
     * Get maps index
     *
     * The maps index is a file containing a summary of all data inside the maps directory. It summarizes into a single
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
            $ns = $this->getRosettaNamespace($mapData);
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
            $codes = [];
            foreach ($this->getAllChildren($mapData) as $child) {
                $udc = $child->attributes($ns, true)->udc;
                if (!empty($udc)) {
                    $udc = explode(',', $udc);
                    foreach ($udc as $code) $codes[] = trim($code);
                }
            }
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
     * Get Rosetta namespace from SVG map
     * @param  \SimpleXMLElement $element XML element
     * @return string|null                Namespace name
     */
    private function getRosettaNamespace(\SimpleXMLElement $element) {
        foreach ($element->getNamespaces() as $namespace=>$value) {
            if ($value === self::MAP_XMLNS) return $namespace;
        }
        return null;
    }


    /**
     * Get all children
     * @param  \SimpleXMLElement   $element XML element
     * @return \SimpleXMLElement[]          Children
     */
    private function getAllChildren(\SimpleXMLElement $element) {
        $res = [];
        foreach ($element->children() as $child) {
            foreach ($this->getAllChildren($child) as $subchild) $res[] = $subchild;
            $res[] = $child;
        }
        return $res;
    }


    /**
     * Load map from disk
     * @param  string   $mapName Map filename
     * @return Map|null          Map instance
     */
    private function loadMap(string $mapName) {
        $mapPath = $this->mapsDir . "/" . $mapName;
        $data = simplexml_load_file($mapPath);
        if (empty($data)) return null;

        // Extract attributes from map data
        $ns = $this->getRosettaNamespace($data);
        $attrs = $data->attributes($ns, true);
        $room = (string) $attrs->room;

        // Parse map data
        unset($attrs->database);
        unset($attrs->locationPattern);
        unset($attrs->room);
        foreach ($this->getAllChildren($data) as $child) {
            $udc = $child->attributes($ns, true)->udc;
            if (empty($udc)) continue;

            $udc = str_replace(', ', ',', $udc);
            $child->addAttribute('data-subjects', $udc);
            unset($child->attributes($ns, true)->udc);
        }

        // Post XML processing of map data
        $data = $data->asXML();
        foreach (["/xmlns:$ns=\"(.+?)\"/", '/<!--(.+?)-->/', '/<\?xml(.+?)\?>/'] as $pattern) {
            $data = preg_replace($pattern, '', $data);
        }
        $data = preg_replace('/\s+/', ' ', $data);
        $data = str_replace('> <', '><', $data);

        // Create and return map instance
        $mapId = count($this->cachedMaps);
        return new Map($mapId, $data, $room);
    }


    /**
     * Returns the name of the map for the given parameters
     * @param  string      $dbId     Database ID
     * @param  string|null $subject  Holding UDC subject code
     * @param  string|null $location Holding location
     * @return string|null           Map name or null if not found
     */
    private function getMapName(string $dbId, ?string $subject, ?string $location=null) {
        if (empty($subject)) return null;
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

        // Find map name from UDC subject
        $mapName = null;
        while (strlen($subject) > 0) {
            if (isset($locationData[$subject])) {
                $mapName = $locationData[$subject];
                break;
            }
            $subject = substr($subject, 0, -1);
        }

        // Return map name
        return $mapName;
    }

}
