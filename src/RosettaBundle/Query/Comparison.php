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


namespace App\RosettaBundle\Query;

class Comparison {
    private $field;
    private $operand = Operand::EQUALS;
    private $value;

    /**
     * Comparison constructor
     * @param  string|array $query Expression or tokens
     * @throws \InvalidArgumentException
     */
    public function __construct($query) {
        $tokens = is_string($query) ? $this->tokenize($query) : $query;
        $this->compile($tokens);
    }


    /**
     * Get field
     * @return string Field
     */
    public function getField(): string {
        return $this->field;
    }


    /**
     * Get operand
     * @return string Operand
     */
    public function getOperand(): string {
        return $this->operand;
    }


    /**
     * Get value
     * @return string Value
     */
    public function getValue(): string {
        return $this->value;
    }


    /**
     * Tokenize expression into an array
     * @param  string   $expr Expression
     * @return string[]       Tokens
     */
    private function tokenize(string $expr) {
        return explode(':', $expr, 2);
    }


    /**
     * Compile sequence into this instance
     * @param  string[] $seq Tokens sequence
     * @throws \InvalidArgumentException
     */
    private function compile(array $seq) {
        if (count($seq) !== 2) {
            throw new \InvalidArgumentException('A comparison query must always contain two tokens');
        }
        list($field, $value) = $seq;

        // Trim quotes from value
        if (
            (mb_strlen($value) > 2) &&
            (($value[0] == '"' && $value[-1] == '"') || ($value[0] == "'" && $value[-1] == "'"))
        ) {
            $value = mb_substr($value, 1, -1);
        }

        // Trim percentages from value
        if ((mb_strlen($value) > 2) && ($value[0] == "%") && ($value[-1] == "%")) {
            $value = mb_substr($value, 1, -1);
            $this->operand = Operand::CONTAINS;
        }

        // Update instance
        $this->field = $field;
        $this->value = $value;
    }

}
