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
use vSymfo\Component\Document\Interfaces\UrlManagerInterface;
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
    protected $options;

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
            'images'     => array()
        ));

        $resolver->setAllowedTypes(array(
            'root_dir'   => 'string',
            'output_dir' => 'string',
            'images'     => 'array'
        ));

        $image = new OptionsResolver();
        $image->setRequired(array('width', 'height', 'format'));
        $image->setDefaults(array(
            'index' => 0,
            'suffix' => '',
            'jpeg_quality' => 80,
            'png_compression_level' => 9,
            'mode' => ImageInterface::THUMBNAIL_OUTBOUND
        ));

        $image->setAllowedTypes(array(
            'index'   => 'integer',
            'suffix'  => 'string',
            'width'   => 'integer',
            'height'  => 'integer',
            'format'  => 'string',
            'jpeg_quality' => 'integer',
            'png_compression_level' => 'integer',
            'mode' => 'string'
        ));

        $image->setAllowedValues(array(
            'format' => array('jpg', 'png'),
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
            'images' => function (Options $options, $value) use($image, $source) {
                $tmp = array();
                foreach ($value as $img) {
                    $opt = $tmp[] =  $image->resolve($img);
                    if (!isset($source[$opt['index']])) {
                        throw new \OutOfRangeException('there is no source with index ' . $opt['index']);
                    }
                }

                return $tmp;
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
     * Podaj tablicę adresów URL do zasobów
     * @return array
     * @throws \Exception
     */
    public function getUrl()
    {
        if (empty($this->options['images'])) {
            return parent::getUrl();
        }

        if (!($this->urlManager instanceof UrlManagerInterface)) {
            throw new \Exception('Wrong UrlManager object. It is not compatible with interface UrlManagerInterface.');
        }

        if (is_null($this->urls)) {
            $this->urls = array();
            foreach ($this->options['images'] as &$image) {
                $this->urls[] = $this->urlManager->url($this->options['output_dir']
                    . DIRECTORY_SEPARATOR . $this->filename($image));
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
