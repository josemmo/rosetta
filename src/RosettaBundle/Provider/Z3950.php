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

use App\RosettaBundle\Entity\AbstractEntity;
use App\RosettaBundle\Entity\Book;
use App\RosettaBundle\Entity\Edition;
use App\RosettaBundle\Entity\Holding;
use App\RosettaBundle\Entity\Institution;
use App\RosettaBundle\Entity\Person;
use App\RosettaBundle\Entity\PhysicalLocation;
use App\RosettaBundle\Entity\Relation;
use App\RosettaBundle\Utils\Marc21Parser;
use App\RosettaBundle\Utils\SearchQuery;

class Z3950 extends AbstractProvider {
    const PRESETS = [
        "millenium" => ["syntax" => "opac"]
    ];

    private static $executed = false;

    private $config;
    private $conn;

    /**
     * @inheritdoc
     */
    public function configure(Institution $institution, SearchQuery $query) {
        self::$executed = false; // Reset flag for all Z39.50 instances

        // Fill presets for configuration
        $config = $institution->getProvider();
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
    }


    /**
     * @inheritdoc
     */
    public function search() {
        if (self::$executed) return;

        $waitConfig = array('timeout' => $this->config['timeout']);
        yaz_wait($waitConfig);

        self::$executed = true;
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
            $result = new \SimpleXMLElement($result);
            $parsedResult = $this->parseResult($result);
            if (!empty($parsedResult)) $results[] = $parsedResult;
        }

        return $results;
    }


    /**
     * Parse MARC21 result
     * @param  \SimpleXMLElement    $rawResult Result in MARC21 XML
     * @return AbstractEntity|false            Parsed result
     */
    private function parseResult(\SimpleXMLElement $rawResult) {
        // TODO: for now, we assume all items are books
        $book = new Book();
        $legalDeposits = [];
        $editions = [];

        // Get bibliography data
        $biblio = $rawResult->bibliographicRecord->record ?? $rawResult;
        foreach ($biblio->datafield as $df) {
            $tag = (string) $df['tag'];
            if ($tag == "017") { /* Legal deposit */
                $volume = Marc21Parser::extractVolume($df->subfield);
                $legalDeposits[$volume] = explode('(', $df->subfield, 2)[0];
            } elseif ($tag == "020") { /* Editions */
                $isbn = explode(' ', $df->subfield, 2)[0];
                $volume = Marc21Parser::extractVolume($df->subfield);
                try {
                    $editions[$volume] = new Edition($isbn, $volume);
                } catch (\Exception $e) {
                    $this->logger->error("Invalid ISBN", [
                        "url" => $this->config['url'],
                        "isbn" => $isbn
                    ]);
                }
            } elseif ($tag == "245") { /* Title */
                foreach ($df->subfield as $subfield) {
                    switch ($subfield['code']) {
                        case 'a':
                            $book->setTitle($subfield);
                            break;
                        case 'b':
                            $book->setSubtitle($subfield);
                            break;
                    }
                }
            } elseif ($tag == "500") { /* Notes */
                $book->addNote($df->subfield);
            } elseif ($tag == "100" || $tag == "700") { /* Authors */
                $firstName = null;
                $lastName = null;
                $relation = Relation::IS_AUTHOR_OF;
                foreach ($df->subfield as $subfield) {
                    switch ($subfield['code']) {
                        case 'a':
                            list($lastName, $firstName) = explode(',', $subfield);
                            break;
                        case 'e':
                            $relation = Marc21Parser::getRelation($subfield);
                            break;
                    }
                }
                if (empty($relation)) {
                    $this->logger->warning("Unknown author relation, assuming author", [
                        "marc21" => $df->asXML()
                    ]);
                    $relation = Relation::IS_AUTHOR_OF;
                }
                $person = Person::of($firstName, $lastName);
                $book->addRelation(new Relation($person, $relation, $book));
            }
        }

        // Add book editions
        foreach ($editions as $volume=>$edition) {
            $edition->setLegalDeposit($legalDeposits[$volume] ?? null);
            $book->addEdition($edition);
        }

        // Get holdings data
        foreach ($rawResult->holdings->holding as $holdingData) {
            $location = new PhysicalLocation(); // TODO
            $holding = new Holding($holdingData->callNumber, $location);
            // TODO: set lent until / loanable

            $target = $editions[''] ?? reset($editions);
            $target->addHolding($holding);
        }

        return $book;
    }

}
