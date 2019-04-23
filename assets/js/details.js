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


/**
 * Get maps cache
 * @return {object} Maps cache
 */
function getMapsCache() {
    const cache = {}

    $('.col-right .map').each(function() {
        const $map = $(this)
        const mapId = $map.data('map')
        cache[mapId] = {}
        $map.find('[data-subjects]').each(function() {
            const subjects = $(this).data('subjects').toString().split(',')
            for (const subject of subjects) {
                if (typeof cache[mapId][subject] == 'undefined') cache[mapId][subject] = []
                cache[mapId][subject].push(this)
            }
        })
    })

    return cache
}


/* INITIALIZE */
$(() => {
    const $colorSource = $('img[data-color-source]')
    if ($colorSource.length !== 1) return

    // Paint heading with vibrant color from cover
    const img = $colorSource[0]
    if (img.naturalWidth > 0) {
        getCoverColor(img)
    } else {
        img.addEventListener('load', () => getCoverColor(img), false)
    }

    // Link holdings to maps
    const $holdingsTable = $('.table-holdings')
    const $mapContainer = $('.col-right')
    const mapsCache = getMapsCache()
    $holdingsTable.find('tr[data-map]').each(function() {
        const $this = $(this)
        const mapId = $this.data('map')
        if (mapId === '') return

        let subject = $this.data('subject').toString()
        while (subject.length > 0) {
            if (typeof mapsCache[mapId][subject] != 'undefined') {
                $this.data('shelves', mapsCache[mapId][subject])
                break
            }
            subject = subject.slice(0, -1)
        }
    }).click(function() {
        $holdingsTable.find('.selected').removeClass('selected')
        $(this).addClass('selected')

        // Hide current map
        $mapContainer.find('.map').hide()
        $mapContainer.find('.highlighted').removeClass('highlighted')

        // Find shelves
        const shelves = $(this).data('shelves')
        if (typeof shelves === 'undefined') return
        for (const shelf of shelves) $(shelf).addClass('highlighted')

        // Show map containing shelves
        $(shelves[0]).parents('.map').show()
    })

    // Select first holding with map data
    $holdingsTable.find('tr[data-map][data-map!=""]').first().trigger('click')
})
