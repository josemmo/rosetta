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

const SEARCH_ATTR = 'data-awaiting-search'

$(() => {
    // Get search container from DOM
    const $container = $(`main[${SEARCH_ATTR}]`)
    if ($container.length !== 1) return
    $container.removeAttr(SEARCH_ATTR)

    // Fetch results from server
    $.post(document.location.href).done((res) => {
        $container.html(res)
    })
})
