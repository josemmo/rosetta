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

use ForceUTF8\Encoding;

class Normalizer {

    /**
     * Fix string encoding
     * @param  string  $text          Input text
     * @param  boolean $fixMillennium Fix Millennium encoding errors
     * @return string                 Normalized text
     */
    public static function fixEncoding(string $text, bool $fixMillennium=false) {
        // Convert from any encoding to UTF-8
        $text = Encoding::toUTF8($text);
        $text = Encoding::fixUTF8($text, Encoding::ICONV_TRANSLIT);

        // Fix Millennium invalid encoding
        if ($fixMillennium) {
            $text = str_replace(
                ['âa', 'âe', 'âi', 'âo', 'âu', 'än', 'âA', 'âE', 'âI', 'âO', 'âU', 'äN', 'Å'],
                [ 'á',  'é',  'í',  'ó',  'ú',  'ñ',  'Á',  'É',  'Í',  'Ó',  'Ú',  'Ñ', '¿'],
                $text
            );
        }
        return $text;
    }


    /**
     * Normalize any string
     * @param  string $text Input text
     * @return string       Normalized text
     */
    public static function normalizeDefault(string $text) {
        $text = trim($text, ' .,:/');
        return $text;
    }


    /**
     * Normalize title
     * @param  string $title Title
     * @return string        Normalized title
     */
    public static function normalizeTitle(string $title) {
        // Remove unnecessary quotations at beginning and end
        $title = trim($title);
        $title = ltrim($title, '[(');
        $title = rtrim($title, '])');

        // Remove text between parentheses or brackets
        $title = preg_replace('/\([^)]+\)/', '', $title);
        $title = preg_replace('/\[[^)]+\]/', '', $title);

        // Fix common errors
        $title = preg_replace('!\s+!', ' ', $title);
        $title = str_replace(' : ', ': ', $title);
        $title = str_replace(' ; ', ': ', $title);

        $title = self::normalizeDefault($title);
        return $title;
    }


    /**
     * Normalize person name
     * @param  string $name Name
     * @return string       Normalized name
     */
    public static function normalizeName(string $name) {
        $name = preg_replace('!\s+!', ' ', $name);

        $newName = [];
        foreach (explode(' ', $name) as $fragment) {
            $length = mb_strlen($fragment);
            if ($length < 2) continue;
            if ($length > 2) $fragment = rtrim($fragment, '.');
            $newName[] = $fragment;
        }

        return implode(' ', $newName);
    }


    /**
     * Normalize slug
     * @param  string $slug Slug
     * @return string       Normalized slug
     */
    public static function normalizeSlug(string $slug) {
        $slug = mb_strtolower($slug);
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        $slug = str_replace(' ', '-', $slug);
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        return $slug;
    }

}
