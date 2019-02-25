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


namespace App\RosettaBundle\Utils;

use App\RosettaBundle\Entity\Relation;

/**
 * Converts raw MARC21 field strings to a machine-readable
 * representation and does some sanitization.
 */
class Marc21Parser {

    /**
     * Extract volume from ISBN or legal deposit
     * @param  string   $input Input string
     * @return int|null        Volume number
     */
    public static function extractVolume(string $input): ?int {
        preg_match('/.+ \(.+\. ([0-9]+)\)/', $input, $matches);
        return empty($matches) ? null : intval($matches[1]);
    }


    /**
     * Get relation code from relation string representation
     * @param  string   $relatorCode MARC21 relator code
     * @return int|null              Relation ID
     */
    public static function getRelation(string $relatorCode): ?int {
        $relatorCode = preg_replace('/[^a-z]/', '', $relatorCode);
        switch ($relatorCode) {
            case 'ed':
            case 'edt':
            case 'edc':
            case 'edm':
                return Relation::IS_EDITOR_OF;
            case 'il':
            case 'ill':
            case 'art':
                return Relation::IS_ILLUSTRATOR_OF;
        }
        return null;
    }

}
