/*
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


import $ from 'jquery'
import Vibrant from 'node-vibrant/dist/vibrant'


/**
 * Get cover color
 * @param {HTMLImageElement} img Cover image
 */
function getCoverColor(img) {
    Vibrant.from(img).getPalette().then((palette) => {
        const color = palette.DarkVibrant.hex
        $('[data-color-target]').css('backgroundColor', color)
    })
}


/* INITIALIZE */
$(() => {
    const $colorSource = $('img[data-color-source]')
    if ($colorSource.length !== 1) return

    const img = $colorSource[0]
    if (img.naturalWidth > 0) {
        getCoverColor(img)
    } else {
        img.addEventListener('load', () => getCoverColor(img), false)
    }
})
