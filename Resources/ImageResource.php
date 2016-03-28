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
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class ImageResource extends ResourceAbstract implements MakeResourceInterface
{
    /**
     * Opcje
     * 
     * @var array
     */
    protected $options = null;

    /**
     * Dane obrazków
     * 
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
            'compare_image_mode' => 'simple'
        ));

        $resolver->setAllowedValues(array(
            'compare_image_mode' => array('simple', 'full'),
        ));

        $resolver->setAllowedTypes(array(
            'root_dir'   => 'string',
            'output_dir' => 'string',
            'images'     => 'array',
            'sizes'      => 'string',
            'media'      => 'array',
            'attr'       => 'array',
            'src-index'  => 'integer',
            'compare_image_mode' => 'string'
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
            'media-index' => -1,
            'use_only_width' => false
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
            'media-index' => 'integer',
            'use_only_width' => 'bool'
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
     * Zapisz pliki
     * 
     * @param array $options
     * 
     * @return array
     */
    public function save(array $options = null)
    {
        $filedata = (is_array($options) && isset($options['filedata'])
            && is_array($options['filedata'])) ? $options['filedata'] : null;

        $imagine = new Imagine();
        $result = array();

        foreach ($this->options['images'] as &$image) {
            // ścieżka pliku do zapisu
            $filename = $this->options['root_dir'] . $this->options['output_dir']
                . DIRECTORY_SEPARATOR . $this->filename($image);

            if (is_null($filedata) || !isset($filedata[$filename])
                || (isset($filedata[$filename]) && !is_array($filedata[$filename]))
                || (isset($filedata[$filename]) && $this->compareImageData($filedata[$filename], $image))
            ) {
                /*
                 * Jeśli zmienna $filedata jest null to nic nie sprawdzaj
                 * i zapisuj odrazu. Jeśli jest tablicą to musi zawierać
                 * informacje o wcześniej zapisanych ilustracjach i nastąpi
                 * sprawdzanie czy nastąpiły jakieś zmiany, jeśli nastąpiły
                 * to trzeba zapisać ilustracje na nowo.
                 */

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

                $openfilename = $this->options['root_dir'] . $this->source[$image['index']];
                if (!empty($this->source[$image['index']]) && file_exists($openfilename)) {
                    // jeśli brak katalogu to utwórz
                    if (!file_exists($this->options['root_dir'] . $this->options['output_dir'])) {
                        mkdir($this->options['root_dir'] . $this->options['output_dir'], 0755, true);
                    }

                    if ($image['use_only_width']) {
                        $imageObj = $imagine->open($openfilename);
                        $imageObj
                            ->resize(new Box($image['width'], ($imageObj->getSize()->getHeight()/($imageObj->getSize()->getWidth()/$image['width']))))
                            ->save($filename, $options)
                        ;
                    } else {
                        $imagine->open($openfilename)
                            ->thumbnail(new Box($image['width'], $image['height']), $image['mode'])
                            ->save($filename, $options)
                        ;
                    }

                    // dane zapisanego pliku
                    $result[$filename] = array(
                        'input' => array(
                            'filename' => $openfilename,
                            'mtime' => @filemtime($openfilename),
                            'size' => @filesize($openfilename)
                        ),
                        'output' => array(
                            'filename' => $filename,
                            //'mtime' => @filemtime($filename),
                            //'size' => @filesize($filename),
                            'width' => $image['width'],
                            'height' => $image['height'],
                            'mode' => $image['mode'],
                            'format' => $image['format'],
                            'options' => $options
                        )
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Porównywanie zapisanych ilustracji ze stanem obecnym.
     * Wartość true oznacza, że nastąpiły jakieś istotne zmiany
     * i należy zapisać ilustracje na nowo.
     * False - nic nie zmieniono => pomiń zapis.
     * 
     * @param array $imageData
     * @param array $image
     * 
     * @return bool
     */
    protected function compareImageData(array &$imageData, array &$image)
    {
        /*
         * Jeśli tablica nie zawiera $imageData['input'] i $imageData['output']
         * lub $this->source[$image['index']] jest puste
         * to nie można przeprowadzić testu.
         */
        if (isset($imageData['input']) && is_array($imageData['input'])
            && isset($imageData['output']) && is_array($imageData['output'])
            && !empty($this->source[$image['index']])
        ) {
            $openfilename = $this->options['root_dir'] . $this->source[$image['index']];
            if (isset($imageData['input']['filename'])
                && $imageData['input']['filename'] != $openfilename
            ) {
                // zmieniono adres pliku źródłowego
                return true;
            }

            if ((isset($imageData['output']['width']) && $imageData['output']['width'] != $image['width'])
                || (isset($imageData['output']['height']) && $imageData['output']['height'] != $image['height'])
            ) {
                // zmieniono ustawienia wymiarów
                return true;
            }

            if (isset($imageData['output']['mode'])
                && $imageData['output']['mode'] != $image['mode']
            ) {
                // zmieniono ustawienia przycinania
                return true;
            }

            if (isset($imageData['output']['format'])
                && $imageData['output']['format'] != $image['format']
            ) {
                // zmieniono format docelowy
                return true;
            }

            if (isset($imageData['output']['format'])
                && $imageData['output']['format'] != $image['format']
            ) {
                // zmieniono format docelowy
                return true;
            }

            if (isset($imageData['output']['options'])) {
                foreach ($imageData['output']['options'] as $name=>$value) {
                    if (isset($image[$name]) && $value != $image[$name]) {
                        // zmieniono inne opcje
                        return true;
                    }
                }
            }

            if ($this->options['compare_image_mode'] == 'full') {
                if (!isset($imageData['output']['filename'])
                    || (isset($imageData['output']['filename'])
                        && !file_exists($imageData['output']['filename']))
                ) {
                    // plik docelowy nie istniał wcześniej
                    return true;
                }

                if ((isset($imageData['input']['mtime']) && $imageData['input']['mtime'] != @filemtime($openfilename))
                    || (isset($imageData['input']['size']) && $imageData['input']['size'] != @filesize($openfilename))
                ) {
                    // zmodyfikowano plik źródłowy
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Czyszczenie
     */
    public function cleanup()
    {
        $result = array();
        $folder = $this->options['root_dir'] . $this->options['output_dir'];
        foreach ($this->options['images'] as &$image) {
            $filename = $folder . DIRECTORY_SEPARATOR . $this->filename($image);
            if (file_exists($filename)) {
                unlink($filename);
                $result[] = $filename;
            }
        }

        if (file_exists($folder) && count(glob($folder . DIRECTORY_SEPARATOR . "*")) === 0) {
            rmdir($folder);
        }

        return $result;
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
     * 
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
     * 
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
