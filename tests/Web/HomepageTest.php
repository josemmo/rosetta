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

class HomepageTest extends WebTestCase {

    /**
     * Test homepage
     */
    public function testHomepage() {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form.search-form');
        $this->assertSelectorExists('form.search-form button[type="submit"]');
    }


    /**
     * Test search form
     */
    public function testSearchForm() {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        // Submit search form
        $form = $crawler->selectButton('Search')->form();
        $form['q'] = "Kurose";
        $crawler = $client->submit($form);

        // Validate redirect URI
        $this->assertContains('/search?q=Kurose', $crawler->getUri());
    }

}
