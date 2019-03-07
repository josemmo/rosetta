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

use App\RosettaBundle\Entity\Work\AbstractWork;

class Innopac extends AbstractHttpProvider {

    /**
     * @inheritdoc
     */
    public function prepare() {
        // Prepare request URL
        $reqUrl = $this->config['url'];
        $reqUrl = str_replace('{{query}}', urlencode($this->query->toInnopac()), $reqUrl);

        // Add request to queue
        $ch = $this->newCurlRequest($reqUrl);
        $this->enqueueRequest($ch);
    }


    /**
     * Parse response
     * @param  string         $res HTML response
     * @return AbstractWork[]      Results
     */
    protected function parseResponse(string &$res) {
        return []; // TODO: not implemented
    }

}
