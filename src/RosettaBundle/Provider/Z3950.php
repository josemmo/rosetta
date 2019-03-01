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


namespace App\RosettaBundle\Provider;

use App\RosettaBundle\Entity\Other\Database;
use App\RosettaBundle\Entity\Other\Holding;
use App\RosettaBundle\Entity\Other\Relation;
use App\RosettaBundle\Entity\Person;
use App\RosettaBundle\Entity\Work\AbstractWork;
use App\RosettaBundle\Entity\Work\Book;
use App\RosettaBundle\Utils\SearchQuery;

class Z3950 extends AbstractProvider {
    const PRESETS = [
        "millenium" => ["syntax" => "opac"]
    ];

    private static $executed = false;
    private static $maxTimeout = 0;

    private $config;
    private $conn;

    /**
     * @inheritdoc
     */
    public function configure(Database $database, SearchQuery $query) {
        self::$executed = false; // Reset flag for all Z39.50 instances

        // Fill presets for configuration
        $config = $database->getProvider();
        if (isset(self::PRESETS[$config['preset']])) {
            foreach (self::PRESETS[$config['preset']] as $prop=>$value) {
                if (empty($config[$prop])) $config[$prop] = $value;
            }
        }
        $this->config = $config;

        // Prepare YAZ instance
        $yazConfig = [];
        if (!is_null($config['user'])) $yazConfig['user'] = $config['user'];
        if (!is_null($config['group'])) $yazConfig['group'] = $config['group'];
        if (!is_null($config['password'])) $yazConfig['password'] = $config['password'];

        // Create YAZ instance
        $conn = yaz_connect($config['url'], $yazConfig);
        yaz_syntax($conn, $config['syntax'] ?? "usmarc");
        yaz_range($conn, 1, $config['max_results']);
        yaz_search($conn, 'rpn', $query->toRpn());
        $this->conn = $conn;

        // Update max timeout
        if ($config['timeout'] > self::$maxTimeout) self::$maxTimeout = $config['timeout'];
    }


    /**
     * @inheritdoc
     */
    public function search() {
        if (self::$executed) return;

        $waitConfig = array('timeout' => self::$maxTimeout);
        yaz_wait($waitConfig);

        self::$executed = true;
        self::$maxTimeout = 0;
    }


    /**
     * @inheritdoc
     */
    public function getResults(): array {
        $error = yaz_error($this->conn);
        if (!empty($error)) {
            $this->logger->error('Failed to get results from Z39.50 provider', [
                'url' => $this->config['url'],
                'yaz_error' => $error
            ]);
            return [];
        }

        // Parse results
        $results = [];
        $hits = yaz_hits($this->conn);
        for ($r=1; $r<=min($this->config['max_results'], $hits); $r++) {
            $result = yaz_record($this->conn, $r, 'xml');
            $result = preg_replace('/xmlns=".+"/', '', $result);
            $result = new \SimpleXMLElement($result);
            $parsedResult = $this->parseResult($result);
            if (!is_null($parsedResult)) $results[] = $parsedResult;
        }

        return $results;
    }


    /**
     * Parse MARC21 result
     * @param  \SimpleXMLElement $rawResult Result in MARC21 XML
     * @return AbstractWork|null            Parsed result
     */
    private function parseResult(\SimpleXMLElement $rawResult) {
        $record = $rawResult->bibliographicRecord->record ?? $rawResult;
        if (empty($record)) return null;

        // Initialize work instance
        $res = null;
        $type = substr($record->leader, 6, 1);
        if ($type == "a" || $type == "t") {
            $res = $this->parseBook($record);
        } else {
            $this->logger->warning('Unknown record type', [
                "leader" => (string) $record->leader,
                "type" => $type,
                "url" => $this->config['url']
            ]);
        }
        if (is_null($res)) return null;

        // Add title
        $title = $record->xpath('datafield[@tag="245"]/subfield[@code="a"]')[0];
        $subtitle = $record->xpath('datafield[@tag="245"]/subfield[@code="b"]');
        if (!empty($subtitle)) $title .= " " . $subtitle[0];
        $res->setTitle($title);

        // Add legal attributes
        foreach ($record->xpath('datafield[@tag="017"]') as $elem) {
            $res->addLegalDeposit($elem->subfield[0]);
        }

        // Add authors
        foreach (['100', '600', '700'] as $tag) {
            foreach ($record->xpath("datafield[@tag='$tag']") as $elem) {
                $name = (string) $elem->xpath('subfield[@code="a"]')[0];
                list($lastname, $firstname) = explode(',', "$name,");

                $type = null;
                $relatorCode = $elem->xpath('subfield[@code="e"]');
                if (!empty($relatorCode)) {
                    $type = $this->getRelation($relatorCode[0]);
                    if (is_null($type)) {
                        $this->logger->warning('Unknown relator code, assuming author', [
                            "firstname" => $firstname,
                            "lastname" => $lastname,
                            "relatorCode" => $relatorCode,
                            "url" => $this->config['url']
                        ]);
                    }
                }
                if (is_null($type)) $type = Relation::IS_AUTHOR_OF;

                $person = new Person();
                $person->setFirstname($firstname);
                $person->setLastname($lastname);
                $res->addRelation(new Relation($person, $type, $res));
            }
        }

        // Add holdings
        if (!empty($rawResult->holdings)) {
            foreach ($rawResult->holdings->holding as $elem) {
                $holding = new Holding($elem->callNumber);
                $res->addHolding($holding);
            }
        }

        return $res;
    }


    /**
     * Parse book
     * @param  \SimpleXMLElement $record MARC21 record
     * @return Book|null                 Book instance
     */
    private function parseBook(\SimpleXMLElement $record) {
        $res = new Book();

        // OCLC numbers
        $oclcTag = $this->config['oclc_field'];
        foreach ($record->xpath("datafield[@tag='$oclcTag']") as $elem) {
            $oclcNumber = preg_replace('/[^0-9]/', '', $elem->subfield[0]);
            $res->addOclcNumber($oclcNumber);
        }

        // ISBN codes
        foreach ($record->xpath('datafield[@tag="020"]') as $elem) {
            $isbn = preg_replace('/\([^)]+\)/','', $elem->subfield[0]);
            $isbn = preg_replace('/[^0-9]/', '', $isbn);
            $res->addIsbn($isbn);
        }

        return $res;
    }


    /**
     * Get relation code from relation string representation
     * @param  string   $relatorCode MARC21 relator code
     * @return int|null              Relation ID
     */
    private function getRelation(string $relatorCode): ?int {
        $relatorCode = preg_replace('/[^a-z]/', '', $relatorCode);
        switch ($relatorCode) {
            case 'ed':
            case 'edt':
            case 'edc':
            case 'edm':
                return Relation::IS_EDITOR_OF;
            case 'il':
            case 'ill':
            case 'art':
                return Relation::IS_ILLUSTRATOR_OF;
        }
        return null;
    }

}
