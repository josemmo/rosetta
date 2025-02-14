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
use App\RosettaBundle\Query\SearchQuery;
use App\RosettaBundle\Utils\HttpClient;

abstract class AbstractHttpProvider extends AbstractProvider {
    private static $executed = false;
    private static $instances = [];

    protected $responses = [];

    /**
     * @inheritdoc
     */
    public function configure(array $config, SearchQuery $query) {
        parent::configure($config, $query);
        self::$executed = false;
    }


    /**
     * @inheritdoc
     */
    public function execute() {
        if (self::$executed) return;

        // Execute requests
        HttpClient::sendQueue();
        foreach (self::$instances as $reqId=>$instance) {
            $instance->__notifyResponse(HttpClient::getResponse($reqId));
        }

        // Reset internal state
        self::$executed = true;
        self::$instances = [];
    }


    /**
     * @inheritdoc
     */
    public function getResults(): array {
        $results = [];
        foreach ($this->responses as &$res) {
            $results = array_merge($results, $this->parseResponse($res));
            unset($res);
        }
        return $results;
    }


    /**
     * Parse response
     * @param  string           $res HTML response
     * @return AbstractEntity[]      Results
     */
    protected abstract function parseResponse(string &$res);


    /**
     * Create new cURL request
     * @param  string   $url Request URL
     * @return resource      cURL resource
     */
    protected function newCurlRequest(string $url) {
        return HttpClient::newRequest($url, $this->config['timeout']);
    }


    /**
     * Enqueue request
     * @param resource $ch cURL resource
     */
    protected function enqueueRequest($ch) {
        $reqId = HttpClient::enqueue($ch);
        self::$instances[$reqId] = $this;
    }


    /**
     * Notify response
     * @param string|null $res Response
     */
    public function __notifyResponse(?string $res) {
        $this->responses[] = $res;
    }

}
