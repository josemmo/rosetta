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

use Nicebooks\Isbn\IsbnTools;

class SearchQuery {
    const OP_AND = "AND";
    const OP_OR = "OR";
    const OP_EQUALS = "EQUALS";
    const RPN_CODES = [
        "title" => 4,
        "isbn" => 7,
        "issn" => 8,
        "date" => 30,
        "subject" => 62,
        "abstract" => 62,
        "author" => 1003,
        "publisher" => 1018,
        "editor" => 1020
    ];

    private static $isbnTools = null;

    private $left = null;
    private $operand = null;
    private $right = null;

    /**
     * SearchQuery constructor
     * @param  string|array $query  Raw query string or query tokens
     * @param  boolean      $strict Whether to throw an exception or fix query if malformed
     * @throws \Exception
     */
    public function __construct($query, $strict=false) {
        if (is_null(self::$isbnTools)) self::$isbnTools = new IsbnTools();

        // Get tokens array
        $tokens = is_array($query) ? $query : $this->tokenizeQuery($query);
        if (!is_array($tokens) || count($tokens) != 3) {
            if ($strict) throw new \Exception("Malformed query string");
            $tokens = $this->getRegularSearchTokens($query);
        }

        // Save tokens to this instance
        $this->left = $tokens[0];
        $this->operand = $tokens[1];
        $this->right = $tokens[2];
    }


    /**
     * Divide query string into tokens
     * @param  string     $query Search query
     * @return array             Tokens
     * @throws \Exception
     */
    private function tokenizeQuery(string $query) {
        $query = trim($query);

        // Parse filters for advanced search
        if (preg_match('/[:\'"]/', $query)) {
            $normalizedQuery = str_replace("'", '"', $query);
            if (
                preg_match('/"[^"]*"(*SKIP)(*F)|\s+/', $normalizedQuery) // Contains spaces outside quotes
            ) {
                return $this->firstTokenization($query);
            }
            if (preg_match('/"[^"]*"(*SKIP)(*F)|:+/', $normalizedQuery)) { // Contains colon (:) outside quotes
                return $this->secondTokenization($query);
            }
        }

        // Is query an ISBN?
        if (self::$isbnTools->isValidIsbn($query)) {
            return ['isbn', self::OP_EQUALS, $query];
        }

        // Fallback to default search
        return $this->getRegularSearchTokens($query);
    }


    /**
     * Tokenizes queries with spaces, quotes and parentheses
     * @param  string     $query Search query
     * @return array             Tokens
     * @throws \Exception
     */
    private function firstTokenization(string $query) {
        $rawTokens = [];
        $buffer = "";
        $quotes = null;
        $parentheses = 0;

        for ($i=0; $i<mb_strlen($query); $i++) {
            $char = mb_substr($query, $i, 1);

            // Update parentheses balance
            if (is_null($quotes)) {
                if ($char === "(") {
                    $parentheses++;
                } elseif ($char === ")") {
                    $parentheses--;
                }
                if ($parentheses !== 0) {
                    $buffer .= $char;
                    continue;
                }
            }

            // Keep track of quoted sentences
            if ($char === '"' || $char === "'") {
                if (is_null($quotes)) {
                    $quotes = $char;
                } elseif ($quotes === $char) {
                    $quotes = null;
                }
            }

            // Dump buffer
            if ($char === " " && is_null($quotes)) {
                $rawTokens[] = $buffer;
                $buffer = "";
                continue;
            }

            // Update buffer
            $buffer .= $char;
        }

        // Dump last characters in buffer
        $rawTokens[] = $buffer;

        // Post-process tokens
        $tokens = [];
        foreach ($rawTokens as $token) {
            $token = trim($token);
            if (empty($token)) continue;

            while (strlen($token) > 1 && $token[0] == "(" && $token[-1] == ")") {
                $token = substr($token, 1, -1);
            }
            $tokens[] = $token;
        }

        return $this->groupTokens($tokens);
    }


    /**
     * Group token array to form a 3-tuple
     * NOTE: does not account for implied parentheses
     * @param  array      $tokens Tokens
     * @return array              Grouped tokens
     * @throws \Exception
     */
    private function groupTokens(array $tokens) {
        $tuple = $tokens[0];
        for ($i=0; $i<=count($tokens)-3; $i+=2) {
            $left = new self($tuple);
            $operand = $tokens[$i+1];
            $right = new self($tokens[$i+2]);
            $tuple = [$left, $operand, $right];
        }
        return $tuple;
    }


    /**
     * Tokenizes queries with colons
     * @param  string $query Search query
     * @return array         Tokens
     */
    private function secondTokenization(string $query) {
        list($field, $value) = explode(':', $query, 2);

        // Remove outer quotes from value
        if (
            ($value[0] === '"' && $value[-1] === '"') ||
            ($value[0] === "'" && $value[-1] === "'")
        ) {
            $value = substr($value, 1, -1);
        }

        return [$field, self::OP_EQUALS, $value];
    }


    /**
     * Get tokens for regular (literal) search
     * @param  string     $query Search query
     * @return array             Tokens
     * @throws \Exception
     */
    private function getRegularSearchTokens($query) {
        return [
            new self(['title', self::OP_EQUALS, "%$query%"]),
            self::OP_OR,
            new self(['author', self::OP_EQUALS, "%$query%"])
        ];
    }


    /**
     * Query to RPN syntax
     * @return string RPN query
     * @throws \Nicebooks\Isbn\Exception\InvalidIsbnException
     */
    public function toRpn(): string {
        $rpn = [];

        if ($this->operand == self::OP_AND || $this->operand == self::OP_OR) {
            $rpn[] = ($this->operand == self::OP_AND) ? "@and" : "@or";
            $rpn[] = $this->left->toRpn();
            $rpn[] = $this->right->toRpn();
        } else {
            $rpn[] = "@attr";
            $rpn[] = "1=" . self::RPN_CODES[$this->left];
            if ($this->left == "isbn") {
                $rpn[] = '"' . self::$isbnTools->format($this->right) . '"';
            } else {
                $rpn[] = '"' . addslashes($this->right) . '"';
            }
        }

        $rpn = implode(' ', $rpn);
        return $rpn;
    }


    /**
     * Query to string
     * @return string Query
     */
    public function __toString() {
        $left = is_string($this->left) ? json_encode($this->left, JSON_UNESCAPED_UNICODE) : $this->left;
        $right = is_string($this->right) ? json_encode($this->right, JSON_UNESCAPED_UNICODE) : $this->right;
        return "($left {$this->operand} $right)";
    }

}
