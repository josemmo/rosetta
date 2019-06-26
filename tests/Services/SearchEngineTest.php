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


namespace App\Tests\Services;

use App\RosettaBundle\Entity\Other\Identifier;
use App\RosettaBundle\Entity\Work\Book;
use App\RosettaBundle\Service\SearchEngine;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchEngineTest extends WebTestCase {

    /**
     * Create dummy entity
     * @param  string[] $ids Identifier values
     * @return Book          Dummy entity
     */
    private function createDummyEntity(array $ids) {
        $entity = new Book();
        $entity->setTitle('Dummy Entity');
        foreach ($ids as $id) {
            $entity->addIdentifier(new Identifier(Identifier::INTERNAL, $id));
        }
        return $entity;
    }


    /**
     * Test group results
     */
    public function testGroupResults() {
        $entities = [];
        $entities[] = $this->createDummyEntity(['red', 'scarlet']);
        $entities[] = $this->createDummyEntity(['green']);
        $entities[] = $this->createDummyEntity(['blue']);
        $entities[] = $this->createDummyEntity(['scarlet']);
        $entities[] = $this->createDummyEntity(['cyan', 'blue']);
        $entities[] = $this->createDummyEntity(['teal', 'olive']);
        $entities[] = $this->createDummyEntity(['olive', 'green']);

        // Get an instance of SearchEngine
        self::bootKernel();
        $searchEngine = self::$container->get(SearchEngine::class);

        // Invoke group results private method
        $reflection = new \ReflectionClass(SearchEngine::class);
        $method = $reflection->getMethod('groupResults');
        $method->setAccessible(true);
        $result = $method->invokeArgs($searchEngine, [$entities]);

        // Validate result
        $expectedResult = ["red,scarlet", "blue,cyan", "green,teal,olive"];
        foreach ($result as $i=>$group) {
            $ids = [];
            foreach ($group as $entity) {
                foreach ($entity->getIdsOfType(Identifier::INTERNAL) as $id) {
                    if (!in_array($id, $ids)) $ids[] = $id;
                }
            }
            $ids = implode(',', $ids);
            $this->assertEquals($expectedResult[$i], $ids);
        }
    }

}
