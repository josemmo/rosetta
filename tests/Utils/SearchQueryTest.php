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

use App\RosettaBundle\Utils\SearchQuery;
use PHPUnit\Framework\TestCase;

class SearchQueryTest extends TestCase {
    const DEFAULT_QUERY = "test";
    const SIMPLE_QUERY = "title:'%la galatea%' AND author:%cervantes% AND publisher:'Project Gutenberg'";
    const ADVANCED_QUERY = "author:'Cervantes, Miguel de' AND (title:%quijote% OR title:'la galatea')";

    /**
     * Test SearchQuery tokenization
     * @throws \Exception
     */
    public function testTokenization() {
        $defaultQuery = (string) new SearchQuery(self::DEFAULT_QUERY);
        $this->assertEquals('("*" EQUALS "%test%")', $defaultQuery);

        $simpleQuery = (string) new SearchQuery(self::SIMPLE_QUERY);
        $simpleExpected = '((("title" EQUALS "%la galatea%") AND ("author" EQUALS "%cervantes%")) AND ' .
                          '("publisher" EQUALS "Project Gutenberg"))';
        $this->assertEquals($simpleExpected, $simpleQuery);

        $advancedQuery = (string) new SearchQuery(self::ADVANCED_QUERY);
        $advancedExpected = '(("author" EQUALS "Cervantes, Miguel de") AND ' .
                            '(("title" EQUALS "%quijote%") OR ("title" EQUALS "la galatea")))';
        $this->assertEquals($advancedExpected, $advancedQuery);
    }


    /**
     * Test RPN query
     * @throws \Nicebooks\Isbn\Exception\InvalidIsbnException
     * @throws \Exception
     */
    public function testRpn() {
        $defaultQuery = (new SearchQuery(self::DEFAULT_QUERY))->toRpn();
        $this->assertEquals('@attr 1=1016 "%test%"', $defaultQuery);

        $simpleQuery = (new SearchQuery(self::SIMPLE_QUERY))->toRpn();
        $simpleExpected = '@and @and @attr 1=4 "%la galatea%" ' .
                          '@attr 1=1003 "%cervantes%" @attr 1=1018 "Project Gutenberg"';
        $this->assertEquals($simpleExpected, $simpleQuery);

        $advancedQuery = (new SearchQuery(self::ADVANCED_QUERY))->toRpn();
        $advancedExpected = '@and @attr 1=1003 "Cervantes, Miguel de" ' .
                            '@or @attr 1=4 "%quijote%" @attr 1=4 "la galatea"';
        $this->assertEquals($advancedExpected, $advancedQuery);
    }

}
