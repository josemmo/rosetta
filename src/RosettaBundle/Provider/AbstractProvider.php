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
use App\RosettaBundle\Entity\Database;
use App\RosettaBundle\Utils\SearchQuery;
use Psr\Log\LoggerInterface;

abstract class AbstractProvider {
    protected $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }


    /**
     * Configure provider
     * This method will be called immediately after the instantiation of the provider.
     *
     * @param Database    $database Database to fetch configuration from
     * @param SearchQuery $query    Search query
     */
    public abstract function configure(Database $database, SearchQuery $query);


    /**
     * Execute search
     * Once all providers have been initialized, a request to execute the search will
     * be sent to each provider.
     */
    public abstract function search();


    /**
     * Get search results
     * @return AbstractEntity[] Search results
     */
    public abstract function getResults(): array;

}
