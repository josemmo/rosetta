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


namespace App\Tests\Web;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchTest extends WebTestCase {

    /**
     * Test search
     */
    public function testSearch() {
        $client = static::createClient();
        $client->request('GET', '/search', ['q' => 'Kurose']);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertSelectorExists('section.leading form.search-form');
        $this->assertSelectorExists('main .spinner');
    }


    /**
     * Test search POST
     */
    public function testSearchPost() {
        $client = static::createClient();
        $crawler = $client->request('POST', '/search', ['q' => 'Kurose']);

        $this->assertGreaterThan(0, $crawler->filter('.entity.book')->count());
    }

}
