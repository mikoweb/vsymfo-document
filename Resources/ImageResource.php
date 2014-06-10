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
use Imagine\Gd\Imagine;
use Imagine\Image\Point;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;

/**
 * Zasób obrazka (wsparcie dla RWD)
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class ImageResource extends ResourceAbstract implements MakeResourceInterface
{
    /**
     * opcje
     * @var array
     */
    protected $options = null;

    /**
     * dane obrazków
     * @var array
     */
    protected $imageData = null;

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
    }

    /**
     * Domyślne opcje
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
        ));

        $resolver->setAllowedTypes(array(
            'root_dir'   => 'string',
            'output_dir' => 'string',
            'images'     => 'array',
            'sizes'      => 'string',
            'media'      => 'array',
            'attr'       => 'array',
            'src-index'  => 'integer',
        ));

        $image = new OptionsResolver();
        $image->setRequired(array('width', 'height', 'format'));
        $image->setDefaults(array(
            'index' => 0,
            'suffix' => '',
            'jpeg_quality' => 80,
            'png_compression_level' => 9,
            'mode' => ImageInterface::THUMBNAIL_OUTBOUND,
            'srcset-w' => 0,
            'srcset-h' => 0,
            'srcset-x' => 0,
            'media-index' => -1
        ));

        $image->setAllowedTypes(array(
            'index'   => 'integer',
            'suffix'  => 'string',
            'width'   => 'integer',
            'height'  => 'integer',
            'format'  => 'string',
            'jpeg_quality' => 'integer',
            'png_compression_level' => 'integer',
            'mode' => 'string',
            'srcset-w' => 'integer',
            'srcset-h' => 'integer',
            'srcset-x' => 'integer',
            'media-index' => 'integer'
        ));

        $image->setAllowedValues(array(
            'format' => array('jpg', 'png', 'gif'),
            'mode' => array(
                ImageInterface::THUMBNAIL_INSET,
                ImageInterface::THUMBNAIL_OUTBOUND
            ),
        ));

        $source = $this->source;
        $resolver->setNormalizers(array(
            'output_dir' => function (Options $options, $value) {
                    if (!file_exists($options['root_dir'] . $value)) {
                        throw new \Exception('not exists directory: ' . $options['root_dir']  . $value);
                    }

                    return $value;
                },
            'media' => function (Options $options, $value) {
                    $tmp = array();
                    foreach ($value as $k => $v) {
                        if (!is_string($v)) {
                            throw new \UnexpectedValueException('option [' . $k . '] is not string');
                        }

                        $tmp[] = $v;
                    }

                    return $tmp;
                },
            'images' => function (Options $options, $value) use($image, $source) {
                    $tmp = array();
                    if (count($value) === 0) {
                        throw new \LengthException('images array cannot be empty');
                    }
                    foreach ($value as $img) {
                        $opt = $tmp[] =  $image->resolve($img);
                        if (!isset($source[$opt['index']])) {
                            throw new \OutOfRangeException('there is no source with index ' . $opt['index']);
                        }

                        if ($opt['media-index'] < -1) {
                            throw new \UnexpectedValueException('media-index must be greater than or equal to -1');
                        }

                        if ($opt['media-index'] > -1 && !isset($options['media'][$opt['media-index']])) {
                            throw new \OutOfRangeException('there is no Media Query with index ' . $opt['media-index']);
                        }
                    }

                    return $tmp;
                },
            'attr' => function (Options $options, $value) {
                    foreach ($value as $k => $v) {
                        if (!is_string($k)) {
                            throw new \UnexpectedValueException('attribute key is not string');
                        }

                        if (!is_string($v)) {
                            throw new \UnexpectedValueException('option [' . $k . '] is not string');
                        }
                    }

                    return $value;
                },
            'src-index' => function (Options $options, $value) {
                    if (!isset($options['images'][$value])) {
                        throw new \OutOfRangeException('there is no image with index ' . $value);
                    }

                    return $value;
                },
        ));
    }

    /**
     * zapisz pliki
     */
    public function save()
    {
        $imagine = new Imagine();

        foreach ($this->options['images'] as &$image) {
            switch ($image['format']) {
                case 'jpg':
                    $options = array('jpeg_quality' => $image['jpeg_quality']);
                    break;
                case 'png':
                    $options = array('png_compression_level' => $image['png_compression_level']);
                    break;
                default:
                    $options = null;
            }

            $imagine->open($this->options['root_dir'] . $this->source[$image['index']])
                ->thumbnail(new Box($image['width'], $image['height']), $image['mode'])
                ->save($this->options['root_dir'] . $this->options['output_dir'] . DIRECTORY_SEPARATOR . $this->filename($image), $options)
            ;
        }
    }

    /**
     * posprzątaj po sobie
     */
    public function cleanup()
    {
        foreach ($this->options['images'] as &$image) {
            unlink($this->options['root_dir'] . $this->options['output_dir'] . DIRECTORY_SEPARATOR . $this->filename($image));
        }
    }

    /**
     * tablica z danymi obrazków pogrupowanymi według atrybutu media
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
                if (!isset($this->imageData[$image['media-index']])) {
                    $this->imageData[$image['media-index']] = array();

                    if ($image['media-index'] !== -1) {
                        $this->imageData['length']++;
                    }
                }

                $item = array(
                    'url' => $urls[$k],
                    'data' => $image
                );

                $this->imageData[$image['media-index']][] = $item;
            }
        }

        return $this->imageData;
    }

    /**
     * Podaj tablicę adresów URL do zasobów
     * @return array
     */
    public function getUrl()
    {
        if (is_null($this->urls)) {
            $this->urls = array();
            foreach ($this->options['images'] as &$image) {
                $this->urls[] = !is_null($this->urlManager)
                    ? $this->urlManager->url($this->options['output_dir']
                        . DIRECTORY_SEPARATOR . $this->filename($image))
                    : $source;
            }
        }

        return $this->urls;
    }

    /**
     * Nazwa nowo wygenerowanego pliku
     * @param array $img
     * @return string
     */
    private function filename(array &$img)
    {
        $path = pathinfo($this->source[$img['index']]);
        return $path['filename']
            . (empty($img['suffix']) ? '' : '_' . $img['suffix'])
            . '_' . $img['width'] . 'x' . $img['height']
            . '.' . $img['format'];
    }
}
