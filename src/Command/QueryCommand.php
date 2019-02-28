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


namespace App\Command;

use App\RosettaBundle\Utils\SearchQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueryCommand extends Command {
    protected static $defaultName = "rosetta:query";

    protected function configure() {
        $this->setDescription('Interprets a query and outputs its internal representation');
        $this->setHelp('Intended for debugging, this command shows the expected SearchQuery of an input');
        $this->addArgument('query', InputArgument::REQUIRED, 'Query to parse');
        $this->addOption(
            'syntax',
            's',
            InputOption::VALUE_OPTIONAL,
            'Output syntax (string, rpn, etc.)',
            ""
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        // Create instance of SearchQuery
        $query = SearchQuery::of($input->getArgument('query'));

        // Get output syntax
        $syntax = ucfirst($input->getOption('syntax'));
        if (method_exists($query, "to$syntax")) {
            $syntax = "to$syntax";
        } else {
            $syntax = "__toString";
        }

        // Send output
        $output->writeln($query->{$syntax}());
    }

}
