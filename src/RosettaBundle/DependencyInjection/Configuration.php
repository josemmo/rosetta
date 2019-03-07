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


namespace App\RosettaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface {

    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder('rosetta');

        // Build configuration tree
        $node = $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('opac')
                    ->children()
                        ->scalarNode('app_name')->end()
                    ->end()
                ->end()
                ->arrayNode('databases')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('id')->end()
                            ->scalarNode('name')->end()
                            ->scalarNode('short_name')->end()
                            ->arrayNode('provider');
        $node =                 $this->attachProviderNode($node)
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('external_providers')
                    ->defaultValue([])
                    ->arrayPrototype();
                    $this->attachProviderNode($node)
                ->end()
            ->end();

        // Fill default values for "get_holdings"
        $treeBuilder->getRootNode()->validate()->always(function($val) {
            foreach ($val['databases'] as &$db) {
                if (is_null($db['provider']['get_holdings'])) $db['provider']['get_holdings'] = true;
            }
            foreach ($val['external_providers'] as &$provider) {
                if (is_null($provider['get_holdings'])) $provider['get_holdings'] = false;
            }
            return $val;
        })->end();

        return $treeBuilder;
    }


    private function attachProviderNode($node) {
        return $node
            ->children()
                ->scalarNode('type')->end()
                ->scalarNode('url')->defaultNull()->end()
                ->scalarNode('preset')->defaultNull()->end()
                ->scalarNode('user')->defaultNull()->end()
                ->scalarNode('group')->defaultNull()->end()
                ->scalarNode('password')->defaultNull()->end()
                ->scalarNode('key')->defaultNull()->end()
                ->scalarNode('country')->defaultNull()->end()
                ->scalarNode('syntax')->defaultNull()->end()
                ->integerNode('oclc_field')->defaultValue(935)->end()
                ->booleanNode('get_holdings')->defaultNull()->end()
                ->integerNode('timeout')
                    ->defaultValue(3)
                    ->min(1)
                ->end()
                ->integerNode('max_results')
                    ->defaultValue(20)
                    ->min(1)
                ->end()
            ->end();
    }

}
