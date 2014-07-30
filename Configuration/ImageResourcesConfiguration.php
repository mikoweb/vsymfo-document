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
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Ustawienia ilustracji
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Configuration
 */
class ImageResourcesConfiguration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('images');
        $rootNode
            ->children()
                ->append($this->addSectionNode('global', false))
                ->append($this->addSectionNode('layout', true))
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @param string $name
     * @param bool $multi
     * @return TreeBuilder
     */
    private function addSectionNode($name, $multi)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name);

        if (!$multi) {
            // ustawienia globalne
            $start = $node->isRequired()->children();
        } else {
            // dla poszczególnych układów
            $start = $node->prototype('array')->children();
        }

        $start
            ->integerNode('src_index')
                ->min(0)
            ->end()
            ->scalarNode('sizes')->end()
            ->arrayNode('attr')
                ->prototype('scalar')
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;

        // gałąź media
        $media = $start->arrayNode('media');

        if (!$multi) {
            // wymagane tylko w globalnych
            $media->isRequired();
        }

        $media
            ->requiresAtLeastOneElement()
            ->prototype('scalar')
                ->cannotBeEmpty()
            ->end()
        ->end();

        // gałąź images
        $images = $start->arrayNode('images');

        if (!$multi) {
            $images->isRequired();
        }

        $imagesChildren = $images
            ->requiresAtLeastOneElement()
            ->prototype('array')
                ->children()
        ;

        $imagesChildren
            ->integerNode('width')
                ->isRequired()
                ->min(1)
            ->end()
            ->integerNode('height')
                ->isRequired()
                ->min(1)
            ->end()
            ->enumNode('format')
                ->isRequired()
                ->values(array('jpg', 'png', 'gif'))
            ->end()
            ->integerNode('index')->min(0)->end()
            ->integerNode('media_index')->min(-1)->end()
            ->scalarNode('suffix')->end()
            ->integerNode('jpeg_quality')
                ->min(0)
                ->max(100)
            ->end()
            ->integerNode('png_compression_level')
                ->min(0)
                ->max(9)
            ->end()
            ->enumNode('mode')
                ->values(array('inset', 'outbound'))
            ->end()
            ->integerNode('srcset-w')
                ->min(0)
            ->end()
            ->integerNode('srcset-h')
                ->min(0)
            ->end()
            ->integerNode('srcset-x')
                ->min(0)
            ->end()
        ;

        $images->end()->end()->end();

        if (!$multi) {
            $start->end();
        } else {
            $start->end()->end()->end();
        }

        return $node;
    }

    /**
     * Ustawienia wybranego układu np. dla samych kategorii.
     * Jeśli w pliku konfiguracyjnym zostanie odnaleziony
     * układ określony drugim argumentem funkcji to funkcja
     * zwróci tablicę array_merge(global, local).
     * @param array $config
     * @param string $layout
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function layoutConfig(array $config, $layout = null)
    {
        if (!is_string($layout) && !is_null($layout)) {
            throw new \InvalidArgumentException("Layout is not string");
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $global = $accessor->getValue($config, "[images][global]");

        if (is_string($layout)) {
            $local = $accessor->getValue($config, "[images][layout][$layout]");
            if (is_array($local)) {
                return array_merge($global, $local);
            }
        }

        return $global;
    }
}
