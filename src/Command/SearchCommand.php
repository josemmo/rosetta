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

use Rosetta\Engine\Engine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SearchCommand extends Command {
    protected static $defaultName = "rosetta:search";
    private $engine;

    public function __construct(Engine $engine) {
        $this->engine = $engine;
        parent::__construct();
    }

    protected function configure() {
        $this->setDescription('Performs a search directly from the terminal');
        $this->setHelp('Performs a search of the provided query using the Rosetta Engine');
        $this->addArgument('query', InputArgument::REQUIRED, 'The terms to search.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $query = $input->getArgument('query');
        $res = $this->engine->search($query);

        $output->writeln($res);
    }
}
