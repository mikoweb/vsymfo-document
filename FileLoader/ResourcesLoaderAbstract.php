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

namespace vSymfo\Component\Document\FileLoader;

use vSymfo\Component\Document\Configuration\ResourcesConfiguration;
use vSymfo\Core\FileLoaderAbstract;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Yaml\Yaml;

/**
 * Loader zasobów dokumentu
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_FileLoader
 */
abstract class ResourcesLoaderAbstract extends FileLoaderAbstract
{
    /**
     * @param OptionsResolver $resolver
     */
    protected function setDefaultOptions(OptionsResolver $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setRequired(array('baseurl', 'resources'));
        $resolver->setDefaults(array(
            'name'    => '',
            'combine' => false,
            'async'   => true
        ));

        $resolver->setAllowedTypes('baseurl', 'string');
        $resolver->setAllowedTypes('name', 'string');
        $resolver->setAllowedTypes('combine', 'bool');
        $resolver->setAllowedTypes('async', 'bool');
        $resolver->setAllowedTypes('resources', 'object');

        $that = $this;
        $resolver->setNormalizer('resources', function (Options $options, $value) use($that) {
            $that->compareOptionType('resources', $value
                , 'vSymfo\Component\Document\Resources\Interfaces\ResourceManagerInterface');
            return $value;
        });
    }

    /**
     * @param string $filename
     * @param ConfigCache $cache
     */
    protected function refreshCache($filename,  ConfigCache $cache)
    {
        $processor = new Processor();
        $content = Yaml::parse($filename);
        $resource = new FileResource($filename);
        if (is_array($content)) {
            $processor->processConfiguration(
                new ResourcesConfiguration(),
                $content
            );
        }

        $this->writeCache($cache, $resource, $content);
    }
}
