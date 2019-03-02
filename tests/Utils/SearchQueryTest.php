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


namespace App\Tests\Utils;

use App\RosettaBundle\Query\SearchQuery;
use PHPUnit\Framework\TestCase;

class SearchQueryTest extends TestCase {
    const DEFAULT_QUERY = "test";
    const SIMPLE_QUERY = "title:'%la galatea%' AND author:%cervantes% AND publisher:'Project Gutenberg'";
    const ADVANCED_QUERY = "author:'Cervantes, Miguel de' AND (title:%quijote% OR title:'la galatea')";

    /**
     * Test SearchQuery tokenization
     */
    public function testTokenization() {
        $defaultQuery = (string) SearchQuery::of(self::DEFAULT_QUERY);
        $this->assertEquals('(<any CONTAINS "test">)', $defaultQuery);

        $simpleQuery = (string) SearchQuery::of(self::SIMPLE_QUERY);
        $simpleExpected = '(<title CONTAINS "la galatea"> AND ' .
                          '<author CONTAINS "cervantes"> AND ' .
                          '<publisher EQUALS "Project Gutenberg">)';
        $this->assertEquals($simpleExpected, $simpleQuery);

        $advancedQuery = (string) SearchQuery::of(self::ADVANCED_QUERY);
        $advancedExpected = '(<author EQUALS "Cervantes, Miguel de"> AND ' .
                            '(<title CONTAINS "quijote"> OR <title EQUALS "la galatea">))';
        $this->assertEquals($advancedExpected, $advancedQuery);
    }


    /**
     * Test RPN query
     */
    public function testRpn() {
        $defaultQuery = (SearchQuery::of(self::DEFAULT_QUERY))->toRpn();
        $this->assertEquals('@or @attr 1=4 "test" @attr 1=1003 "test"', $defaultQuery);

        $simpleQuery = (SearchQuery::of(self::SIMPLE_QUERY))->toRpn();
        $simpleExpected = '@and @attr 1=4 "la galatea" @and ' .
                          '@attr 1=1003 "cervantes" @attr 1=1018 "Project Gutenberg"';
        $this->assertEquals($simpleExpected, $simpleQuery);

        $advancedQuery = (SearchQuery::of(self::ADVANCED_QUERY))->toRpn();
        $advancedExpected = '@and @attr 1=1003 "Cervantes, Miguel de" ' .
                            '@or @attr 1=4 "quijote" @attr 1=4 "la galatea"';
        $this->assertEquals($advancedExpected, $advancedQuery);
    }


    /**
     * Test INNOPAC query
     */
    public function testInnopac() {
        $defaultQuery = (SearchQuery::of(self::DEFAULT_QUERY))->toInnopac();
        $this->assertEquals('"test"', $defaultQuery);

        $simpleQuery = (SearchQuery::of(self::SIMPLE_QUERY))->toInnopac();
        $simpleExpected = 't:"la galatea" and a:"cervantes" and "Project Gutenberg"';
        $this->assertEquals($simpleExpected, $simpleQuery);

        $advancedQuery = (SearchQuery::of(self::ADVANCED_QUERY))->toInnopac();
        $advancedExpected = 'a:"Cervantes, Miguel de" and (t:"quijote" or t:"la galatea")';
        $this->assertEquals($advancedExpected, $advancedQuery);
    }


    /**
     * Test malformed queries
     */
    public function testMalformedQueries() {
        $input = '"this (isn\'t a valid:query))';
        $expected = '(<any CONTAINS ' . json_encode($input, JSON_UNESCAPED_UNICODE) . '>)';
        $output = (string) SearchQuery::of($input);
        $this->assertEquals($expected, $output);
    }

}
