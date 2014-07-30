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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Yaml\Yaml;
use vSymfo\Core\FileLoaderAbstract;
use vSymfo\Component\Document\Configuration\ImageResourcesConfiguration;
use vSymfo\Component\Document\Resources\ImageResource;

/**
 * Loader ilustracji
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
     * Domyślne opcje
     * @param OptionsResolver $resolver
     */
    protected function setDefaultOptions(OptionsResolver $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setRequired(array('images_root_dir', 'images_output_dir', 'baseurl'));
        $resolver->setDefaults(array(
                'layout'    => null,
                'forcesave' => false,
                'checksum'  => null
            ));

        $resolver->setAllowedTypes(array(
                'forcesave' => 'bool',
                'images_root_dir' => 'string',
                'images_output_dir' => 'string',
                'baseurl' => 'string'
            ));
    }

    /**
     * @param string $filename
     * @param ConfigCache $cache
     */
    protected function refreshCache($filename, ConfigCache $cache)
    {
        $content = Yaml::parse($filename);
        $resource = new FileResource($filename);
        $processor = new Processor();
        if (is_array($content)) {
            $processor->processConfiguration(
                new ImageResourcesConfiguration(),
                $content
            );
        }

        $this->writeCache($cache, $resource, $content);
    }

    /**
     * @param string $filename
     * @param null|string $type
     * @return void
     */
    protected function process($filename, $type = null)
    {
        $config = ImageResourcesConfiguration::layoutConfig(self::$yaml[$filename], $this->options['layout']);
        $this->loadImages($config, $type);
    }

    /**
     * Wczytywanie obrazków
     * @param array $config
     * @param null|string $type
     */
    abstract protected function loadImages(array &$config, $type = null);

    /**
     * Zapisywanie obrazków
     * @param ImageResource $res
     * @param array $config
     * @param array $options
     */
    abstract public function saveImages(ImageResource $res, array &$config, array $options = null);

    /**
     * Tworzenie zasobu graficznego
     * @param string $title
     * @param array $images
     * @param array $config
     * @return ImageResource
     */
    abstract protected function createImageResource($title, array &$images, array &$config);
}
