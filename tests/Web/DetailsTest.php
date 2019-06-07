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

class DetailsTest extends WebTestCase {

    /**
     * Test details page
     */
    public function testDetails() {
        $client = static::createClient();

        // Do a regular search and open first result
        $crawler = $client->request('POST', '/search', ['q' => 'Kurose']);
        $detailsUri = $crawler->filter('.search-results .entity.book')->first()
            ->filter('a')->first()
            ->attr('href');

        // Load details page
        $client->request('GET', $detailsUri);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('main aside.details-column.col-left');
        $this->assertSelectorExists('main section.details-column.col-middle');
    }

}
