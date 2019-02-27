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


namespace App\Controller;

use App\RosettaBundle\Service\ConfigEngine;
use App\RosettaBundle\Service\SearchEngine;
use App\RosettaBundle\Utils\SearchQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController {

    /**
     * @Route("/search", methods={"POST"}, name="search_post")
     */
    public function getResults(Request $request, ConfigEngine $config, SearchEngine $engine) {
        // Get query and database
        $query = new SearchQuery($request->get('q'));
        $db = $config->getCurrentDatabase();
        $dbIds = empty($db) ? null : [$db->getId()];

        // Render results
        $results = $engine->search($query, $dbIds);
        return $this->render("pages/search_post.html.twig", [
            "results" => $results
        ]);
    }


    /**
     * @Route("/search", name="search")
     */
    public function search() {
        return $this->render("pages/search.html.twig");
    }

}
