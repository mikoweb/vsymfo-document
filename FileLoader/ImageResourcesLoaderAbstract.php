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

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;
use vSymfo\Core\FileLoaderAbstract;
use vSymfo\Component\Document\Configuration\ImageResourcesConfiguration;

/**
 * Images resources loader.
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_FileLoader
 */
abstract class ImageResourcesLoaderAbstract extends FileLoaderAbstract
{
    /**
     * @var bool
     */
    protected $save = false;

    /**
     * @param FileLocatorInterface $locator
     * @param array $options
     */
    public function __construct(FileLocatorInterface $locator, array $options)
    {
        parent::__construct($locator, $options);
        if ($this->options["forcesave"]) $this->save = true;
        else $this->save = false;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function setDefaultOptions(OptionsResolver $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setRequired(array('images_root_dir', 'images_output_dir', 'baseurl'));
        $resolver->setDefaults(array(
            'forcesave' => false
        ));

        $resolver->setAllowedTypes('forcesave', 'bool');
        $resolver->setAllowedTypes('images_root_dir', 'string');
        $resolver->setAllowedTypes('images_output_dir', 'string');
        $resolver->setAllowedTypes('baseurl', 'string');
    }

    /**
     * @param string $filename
     * @param ConfigCache $cache
     */
    protected function refreshCache($filename, ConfigCache $cache)
    {
        $content = Yaml::parse(file_get_contents($filename));
        $resource = new FileResource($filename);
        $processor = new Processor();
        $processor->processConfiguration(
            new ImageResourcesConfiguration(),
            is_null($content) ? [] : $content
        );

        $this->writeCache($cache, $resource, $content);
    }

    /**
     * @param string $filename
     * @param null|string $type
     * @return void
     */
    protected function process($filename, $type = null)
    {
        $config = ImageResourcesConfiguration::layoutConfig(self::$yaml[$filename], $type);
        $this->loadImages($config, self::$yaml[$filename], $type);
    }

    /**
     * Processing a configuration of the images.
     * 
     * @param array $config
     * @param array $fullConfig
     * @param null|string $type
     */
    abstract protected function loadImages(array $config, array $fullConfig, $type = null);
}
