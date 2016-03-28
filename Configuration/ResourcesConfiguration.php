<?php

/*
 * This file is part of the vSymfo package.
 *
 * website: www.vision-web.pl
 * (c) Rafał Mikołajun <rafal@vision-web.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace vSymfo\Component\Document\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Plik konfiguracyjny zasobów dokumentu
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Configuration
 */
class ResourcesConfiguration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('resources');
        $rootNode
            ->prototype('array')
                ->children()
                    ->arrayNode('groups')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('name')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->arrayNode('required')
                                    ->isRequired()
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('sources')
                        ->prototype('array')
                            ->isRequired()
                            ->useAttributeAsKey('path')
                            ->prototype('scalar')
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
