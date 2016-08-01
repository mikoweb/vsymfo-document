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

namespace vSymfo\Component\Document\Resources;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use vSymfo\Component\Document\Resources\Interfaces\MakeResourceInterface;
use vSymfo\Component\Document\ResourceAbstract;
use Imagine\Image\ImageInterface;
use vSymfo\Component\Document\Resources\Storage\ImagesStorageInterface;

/**
 * Zasób obrazka (wsparcie dla RWD)
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class ImageResource extends ResourceAbstract implements MakeResourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var array
     */
    protected $imageData;

    /**
     * @var ImagesStorageInterface
     */
    protected $imagesStorage;

    /**
     * @param string name
     * @param array $source
     * @param array $options
     */
    public function __construct($name, array $source, array $options = array())
    {
        parent::__construct($name, $source, $options);
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
        $this->imageData = null;
        $this->imagesStorage = null;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array('root_dir', 'output_dir'));
        $resolver->setDefaults(array(
            'images' => array(),
            'sizes'  => '',
            'media'  => array(),
            'attr'   => array(),
            'src-index' => 0,
            'compare_image_mode' => 'simple',
            'filter' => null,
            'alt_property' => null,
        ));

        $resolver->setAllowedValues('compare_image_mode', ['simple', 'full']);

        $resolver->setAllowedTypes('root_dir', 'string');
        $resolver->setAllowedTypes('output_dir', 'string');
        $resolver->setAllowedTypes('images', 'array');
        $resolver->setAllowedTypes('sizes', 'string');
        $resolver->setAllowedTypes('media', 'array');
        $resolver->setAllowedTypes('attr', 'array');
        $resolver->setAllowedTypes('src-index', 'integer');
        $resolver->setAllowedTypes('compare_image_mode', 'string');
        $resolver->setAllowedTypes('filter', ['string', 'null']);
        $resolver->setAllowedTypes('alt_property', ['string', 'null']);

        $image = new OptionsResolver();
        $image->setRequired(array('width', 'height'));
        $image->setDefaults(array(
            'index' => 0,
            'suffix' => '',
            'jpeg_quality' => 80,
            'png_compression_level' => 9,
            'mode' => ImageInterface::THUMBNAIL_OUTBOUND,
            'srcset_w' => 0,
            'srcset_h' => 0,
            'srcset_x' => 0,
            'media_index' => -1,
            'use_only_width' => false,
            'format' => 'jpg',
        ));

        $image->setAllowedTypes('index', 'integer');
        $image->setAllowedTypes('suffix', 'string');
        $image->setAllowedTypes('width', 'integer');
        $image->setAllowedTypes('height', 'integer');
        $image->setAllowedTypes('format', 'string');
        $image->setAllowedTypes('jpeg_quality', 'integer');
        $image->setAllowedTypes('png_compression_level', 'integer');
        $image->setAllowedTypes('mode', 'string');
        $image->setAllowedTypes('srcset_w', 'integer');
        $image->setAllowedTypes('srcset_h', 'integer');
        $image->setAllowedTypes('srcset_x', 'integer');
        $image->setAllowedTypes('media_index', 'integer');
        $image->setAllowedTypes('use_only_width', 'bool');

        $image->setAllowedValues('format', ['jpg', 'png', 'gif']);
        $image->setAllowedValues('mode', [
            ImageInterface::THUMBNAIL_INSET,
            ImageInterface::THUMBNAIL_OUTBOUND
        ]);

        $source = $this->source;
        $resolver->setNormalizer('media', function (Options $options, $value) {
            $tmp = array();
            foreach ($value as $k => $v) {
                if (!is_string($v)) {
                    throw new \UnexpectedValueException('option [' . $k . '] is not string');
                }

                $tmp[] = $v;
            }

            return $tmp;
        });

        $resolver->setNormalizer('images', function (Options $options, $value) use($image, $source) {
            $tmp = array();
            if (count($value) === 0) {
                throw new \LengthException('images array cannot be empty');
            }
            foreach ($value as $img) {
                $opt = $tmp[] =  $image->resolve($img);
                if (!isset($source[$opt['index']])) {
                    throw new \OutOfRangeException('there is no source with index ' . $opt['index']);
                }

                if ($opt['media_index'] < -1) {
                    throw new \UnexpectedValueException('media_index must be greater than or equal to -1');
                }

                if ($opt['media_index'] > -1 && !isset($options['media'][$opt['media_index']])) {
                    throw new \OutOfRangeException('there is no Media Query with index ' . $opt['media_index']);
                }
            }

            return $tmp;
        });

        $resolver->setNormalizer('attr', function (Options $options, $value) {
            foreach ($value as $k => $v) {
                if (!is_string($k)) {
                    throw new \UnexpectedValueException('attribute key is not string');
                }

                if (!is_string($v)) {
                    throw new \UnexpectedValueException('option [' . $k . '] is not string');
                }
            }

            return $value;
        });

        $resolver->setNormalizer('src-index', function (Options $options, $value) {
            if (!isset($options['images'][$value])) {
                throw new \OutOfRangeException('there is no image with index ' . $value);
            }

            return $value;
        });
    }

    /**
     * Tablica z danymi obrazków pogrupowanymi według atrybutu media
     *
     * @return array
     */
    public function imageData()
    {
        if (is_null($this->imageData)) {
            $urls = $this->getUrl();
            $this->imageData = array(
                'sizes' => $this->options['sizes'],
                'media' => $this->options['media'],
                'attr'  => $this->options['attr'],
                'src-index' => $this->options['src-index'],
                'length' => 0,
            );

            foreach ($this->options['images'] as $k => $image) {
                if (!isset($this->imageData[$image['media_index']])) {
                    $this->imageData[$image['media_index']] = array();

                    if ($image['media_index'] !== -1) {
                        $this->imageData['length']++;
                    }
                }

                $item = array(
                    'url' => $urls[$k],
                    'data' => $image
                );

                $this->imageData[$image['media_index']][] = $item;
            }
        }

        return $this->imageData;
    }

    /**
     * @param ImagesStorageInterface $imagesStorage
     */
    public function setImagesStorage(ImagesStorageInterface $imagesStorage = null)
    {
        $this->imagesStorage = $imagesStorage;
        $this->imagesStorage->setOptions($this->options);
        $this->imagesStorage->setSources($this->source);
        $this->imagesStorage->setUrlManager($this->urlManager);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $options = null)
    {
        $this->throwIfNoStorage();
        return $this->imagesStorage->save($options);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
        $this->throwIfNoStorage();
        return $this->imagesStorage->cleanup();
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        $this->throwIfNoStorage();
        return $this->imagesStorage->getUrls();
    }

    protected function throwIfNoStorage()
    {
        if (is_null($this->imagesStorage)) {
            throw new \RuntimeException('ImagesStorage not found');
        }
    }
}
