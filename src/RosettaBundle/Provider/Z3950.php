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

use App\RosettaBundle\Entity\Organization;
use App\RosettaBundle\Entity\Other\Holding;
use App\RosettaBundle\Entity\Other\Relation;
use App\RosettaBundle\Entity\Person;
use App\RosettaBundle\Entity\Thing;
use App\RosettaBundle\Entity\Work\AbstractWork;
use App\RosettaBundle\Entity\Work\Book;
use App\RosettaBundle\Utils\Normalizer;

class Z3950 extends AbstractProvider {
    private static $executed = false;
    private static $maxTimeout = 0;

    private $conn;

    /**
     * @inheritdoc
     */
    public function prepare() {
        self::$executed = false;

        // Prepare YAZ instance
        $yazConfig = [];
        if (!is_null($this->config['user'])) $yazConfig['user'] = $this->config['user'];
        if (!is_null($this->config['group'])) $yazConfig['group'] = $this->config['group'];
        if (!is_null($this->config['password'])) $yazConfig['password'] = $this->config['password'];
        if (!is_null($this->config['charset'])) $yazConfig['charset'] = $this->config['charset'];

        // Create YAZ instance
        $conn = yaz_connect($this->config['url'], $yazConfig);
        yaz_syntax($conn, $this->config['syntax'] ?? "usmarc");
        yaz_range($conn, 1, $this->config['max_results']);
        yaz_search($conn, 'rpn', $this->query->toRpn());
        $this->conn = $conn;

        // Update max timeout
        if ($this->config['timeout'] > self::$maxTimeout) {
            self::$maxTimeout = $this->config['timeout'];
        }
    }


    /**
     * @inheritdoc
     */
    public function execute() {
        if (self::$executed) return;

        // Execute all search queries
        $waitConfig = array('timeout' => self::$maxTimeout);
        yaz_wait($waitConfig);

        // Reset flags
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
        $isMillennium = ($this->config['preset'] == "millennium");
        for ($r=1; $r<=min($this->config['max_results'], $hits); $r++) {
            $result = yaz_record($this->conn, $r, 'xml');
            $result = Normalizer::fixEncoding($result, $isMillennium);
            $result = preg_replace('/xmlns=".+"/', '', $result);
            $result = new \SimpleXMLElement($result);
            $parsedResult = $this->parseResult($result);
            if (!is_null($parsedResult)) $results[] = $parsedResult;
        }

        return $results;
    }


    /**
     * @inheritdoc
     */
    protected function getPresets(): array {
        return [
            "millennium" => ["syntax" => "opac"]
        ];
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
        } elseif ($type == "r") {
            $res = $this->parseThing($record);
        } else {
            $this->logger->warning('Unknown record type', [
                "leader" => (string) $record->leader,
                "type" => $type,
                "url" => $this->config['url']
            ]);
        }
        if (is_null($res)) return null;

        // Add internal identifier
        $controlNumbers = $record->xpath('controlfield[@tag="001"]');
        foreach ($controlNumbers as $cNumber) {
            $res->addInternalId($this->config['id'], $cNumber);
        }

        // Add work attributes
        if ($res instanceof AbstractWork) $this->fillInWork($res, $record);

        // Add holdings
        if ($this->config['get_holdings'] && !empty($rawResult->holdings)) {
            foreach ($rawResult->holdings->holding as $elem) {
                $holding = new Holding($elem->callNumber);
                $holding->setLocationName($elem->localLocation);
                if ($elem->publicNote == "NOT AVAILABLE") {
                    $holding->setAvailable(false);
                } elseif ($elem->publicNote != "AVAILABLE") {
                    $holding->setLoanable(false);
                }
                $res->addHolding($holding);
            }
        }

        return $res;
    }


    /**
     * Parse thing
     * @param  \SimpleXMLElement $record MARC21 record
     * @return Thing|null                Thing instance
     */
    private function parseThing(\SimpleXMLElement $record) {
        $thing = new Thing();

        // Name
        $name = $record->xpath('datafield[@tag="245"]/subfield[@code="a"]')[0];
        $name = Normalizer::normalizeTitle($name);
        $thing->setName($name);

        return $thing;
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

        // Add cover
        if (!is_null($this->config['covers_url'])) {
            $imageUrl = $this->config['covers_url'];
            $imageUrl = str_replace('{{isbn10}}', $res->getIsbn10s()[0] ?? '', $imageUrl);
            $imageUrl = str_replace('{{isbn13}}', $res->getIsbn13s()[0] ?? '', $imageUrl);
            $res->setImageUrl($imageUrl);
        }

        return $res;
    }


    /**
     * Fill-in work
     * @param AbstractWork              AbstractWork instance
     * @param \SimpleXMLElement $record MARC21 record
     */
    private function fillInWork($work, \SimpleXMLElement $record) {
        // Parse title
        $title = $record->xpath('datafield[@tag="245"]/subfield[@code="a"]')[0];
        $subtitle = $record->xpath('datafield[@tag="245"]/subfield[@code="b"]');
        if (!empty($subtitle)) $title .= " " . $subtitle[0];
        $title = Normalizer::normalizeTitle($title);
        $title = explode(':', $title, 2);

        // Add title
        $work->setTitle(trim($title[0]));
        if (isset($title[1])) {
            $subtitle = trim($title[1]);
            if (!empty($subtitle)) $work->setSubtitle($subtitle);
        }

        // Add legal attributes
        foreach ($record->xpath('datafield[@tag="017"]') as $elem) {
            $work->addLegalDeposit($elem->subfield[0]);
        }

        // Add publisher
        $publisher = $record->xpath('datafield[@tag="260"]/subfield[@code="b"]');
        if (!empty($publisher)) {
            $organization = new Organization();
            $organization->setName(Normalizer::normalizeDefault($publisher[0]));
            $work->addPublisher($organization);
        }

        // Add published year
        $pubYear = $record->xpath('datafield[@tag="260"]/subfield[@code="c"]');
        if (!empty($pubYear)) {
            preg_match('/[0-9]{4}/', $pubYear[0], $matches);
            if (!empty($matches)) $work->setPubDate($matches[0]);
        }

        // Add authors
        foreach (['100', '600', '700'] as $tag) {
            foreach ($record->xpath("datafield[@tag='$tag']") as $elem) {
                $name = (string) $elem->xpath('subfield[@code="a"]')[0];
                list($lastname, $firstname) = explode(',', "$name,");
                $name = Normalizer::normalizeName("$firstname $lastname");

                $type = null;
                $relatorCode = $elem->xpath('subfield[@code="e"]');
                if (!empty($relatorCode)) {
                    $type = $this->getRelation($relatorCode[0]);
                    if (is_null($type)) {
                        $this->logger->warning('Unknown relator code, assuming author', [
                            "name" => $name,
                            "relatorCode" => (string) $relatorCode[0],
                            "url" => $this->config['url']
                        ]);
                    }
                }
                if (is_null($type)) $type = Relation::IS_AUTHOR_OF;

                $person = new Person();
                $person->setName($name);
                $work->addRelation(new Relation($person, $type, $work));
            }
        }
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
