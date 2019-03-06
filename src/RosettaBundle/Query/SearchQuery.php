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

use Nicebooks\Isbn\IsbnTools;

class SearchQuery {
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
    const INNOPAC_CODES = [
        "title" => "t",
        "subject" => "s",
        "author" => "a"
    ];

    private static $isbnTools = null;

    private $operand;
    private $items = [];

    /**
     * Static constructor
     * @param  string|array $query Query string or tokens
     * @return static              SearchQuery instance
     */
    public static function of($query): self {
        try {
            return new self($query);
        } catch (\Exception $e) {
            return self::getLiteralSearch($query);
        }
    }


    /**
     * Get tokens for literal search
     * @param  string $query Query string
     * @return static        SearchQuery instance
     */
    private static function getLiteralSearch(string $query): self {
        $seq = ["any", "%$query%"];
        if (self::$isbnTools->isValidIsbn($query)) $seq = ["isbn", $query];

        $comparison = new Comparison($seq);
        return new self([$comparison]);
    }


    /**
     * SearchQuery constructor
     * @param  string|array $query Query string or tokens
     * @throws \InvalidArgumentException
     */
    public function __construct($query) {
        if (is_null(self::$isbnTools)) self::$isbnTools = new IsbnTools();

        $tokens = is_string($query) ? $this->tokenize($query) : $query;
        $this->compile($tokens);
    }


    /**
     * Get operand
     * @return string Operand
     */
    public function getOperand(): string {
        return $this->operand;
    }


    /**
     * Get items
     * @return mixed[] Items
     */
    public function getItems(): array {
        return $this->items;
    }


    /**
     * Tokenize query string into an array
     * @param  string   $query Query
     * @return string[]        Tokens
     */
    private function tokenize(string $query) {
        // Trim outer parentheses
        while ((mb_strlen($query) > 2) && ($query[0] == "(") && ($query[-1] == ")")) {
            $query = mb_substr($query, 1, -1);
        }

        // Explode query by spaces while respecting escape characters
        $seq = [];
        $buf = "";
        $quote = null;
        $i = 0;
        while ($i < mb_strlen($query)) {
            $c = mb_substr($query, $i, 1);
            if ($c == "(") {
                if (!empty($buf)) $seq[] = trim($buf);
                $buf = "";
                $last = mb_strrpos($query, ")", $i);
                $seq[] = mb_substr($query, $i + 1, $last - $i - 1);
                $i = $last + 1;
            } elseif ($c == " " && is_null($quote)) {
                if (!empty($buf)) $seq[] = trim($buf);
                $buf = "";
                $i++;
            } else {
                if ($c === $quote) {
                    $quote = null;
                } elseif (is_null($quote) && $c == '"' || $c == "'") {
                    $quote = $c;
                }
                $buf .= $c;
                $i++;
            }
        }
        if (!empty($buf)) $seq[] = trim($buf);

        return $seq;
    }


    /**
     * Compile sequence into this instance
     * @param  string[] $seq Tokens sequence
     * @throws \InvalidArgumentException
     */
    private function compile(array $seq) {
        $this->operand = null;
        $this->items = [];

        $expectsOperand = false;
        foreach ($seq as $i=>$expr) {
            if (!$expectsOperand) {
                $this->items[] = is_string($expr) ? $this->compileExpression($expr) : $expr;
            } else {
                if (!Operand::isLogicalOperand($expr)) {
                    throw new \InvalidArgumentException('Invalid logical operand');
                }
                if (is_null($this->operand)) {
                    $this->operand = $expr;
                } elseif ($expr !== $this->operand) {
                    // Overwrite this instance in case of mixed operands
                    $oldSequence = new self($this->toSequence());
                    $newSequence = array_slice($seq, $i);
                    array_unshift($newSequence, $oldSequence);
                    $this->compile($newSequence);
                    break;
                }
            }
            $expectsOperand = !$expectsOperand;
        }
    }


    /**
     * Compile expression
     * @param  string                 $expr Expression
     * @return SearchQuery|Comparison       Query item
     * @throws \InvalidArgumentException
     */
    private function compileExpression(string $expr) {
        // Detect partially-compiled expression
        if (preg_match_all('/"[^"]*"(*SKIP)(*F)|:+/', $expr) > 1) return new self($expr);

        // Fallback to comparison
        return new Comparison($expr);
    }


    /**
     * Returns a sequence representation of the query
     * @return array Sequence
     */
    public function toSequence(): array {
        $res = [];
        $lastIndex = count($this->items) - 1;
        foreach ($this->items as $i=>$item) {
            $res[] = $item;
            if ($i < $lastIndex) $res[] = $this->operand;
        }
        return $res;
    }


    /**
     * To string representation
     */
    public function __toString() {
        $res = [];

        $lastIndex = count($this->items) - 1;
        foreach ($this->items as $i=>$item) {
            if ($item instanceof Comparison) {
                $res[] = "<" . $item->getField();
                $res[] = $item->getOperand();
                $res[] = json_encode($item->getValue(), JSON_UNESCAPED_UNICODE) . ">";
            } else {
                $res[] = $item->__toString();
            }
            if ($i < $lastIndex) $res[] = $this->operand;
        }

        $res = implode(' ', $res);
        return "($res)";
    }


    /**
     * Query to RPN syntax
     * @return string RPN query
     */
    public function toRpn(): string {
        $res = [];

        $lastIndex = count($this->items) - 1;
        foreach ($this->items as $i=>$item) {
            // Add query operand
            if (!is_null($this->operand) && ($i < $lastIndex)) {
                $res[] = ($this->operand == Operand::AND) ? "@and" : "@or";
            }

            // Nested queries
            if ($item instanceof self) {
                $res[] = $item->toRpn();
                continue;
            }

            // Fix for "any" field, as most Z39.50 servers don't support it
            if ($item->getField() == "any") {
                $res[] = self::of([
                    new Comparison(['title', "%" . $item->getValue() . "%"]),
                    Operand::OR,
                    new Comparison(['author', "%" . $item->getValue() . "%"])
                ])->toRpn();
                continue;
            }

            // Add comparison item
            $field = self::RPN_CODES[$item->getField()] ?? self::RPN_CODES['title'];
            $value = $item->getValue();
            $res[] = "@attr";
            $res[] = "1=$field";
            $res[] = '"' . addslashes($value) . '"';
        }

        $res = implode(' ', $res);
        return $res;
    }


    /**
     * To INNOPAC search query
     * @return string INNOPAC query
     */
    public function toInnopac(): string {
        $res = [];

        $lastIndex = count($this->items) - 1;
        foreach ($this->items as $i=>$item) {
            // Add query items
            if ($item instanceof Comparison) {
                $field = self::INNOPAC_CODES[$item->getField()] ?? null;
                if (!empty($field)) $res[] = "$field:";
                $res[] = '"' . addslashes($item->getValue()) . '"';
            } else {
                $res[] = "(" . $item->toInnopac() . ")";
            }

            // Add query operand
            if (!is_null($this->operand) && ($i < $lastIndex)) {
                $res[] = ($this->operand == Operand::AND) ? " and " : " or ";
            }
        }

        $res = implode('', $res);
        return $res;
    }

}
