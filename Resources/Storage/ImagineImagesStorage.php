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

namespace vSymfo\Component\Document\Resources\Storage;

use Imagine\Gd\Imagine;
use Imagine\Image\Point;
use Imagine\Image\Box;
use vSymfo\Component\Document\Interfaces\UrlManagerInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources_Storage
 */
class ImagineImagesStorage implements ImagesStorageInterface
{
    /**
     * @var array
     */
    private $urls;

    /**
     * @var array
     */
    private $options;

    /**
     * @var UrlManagerInterface
     */
    private $urlManager;

    /**
     * @var array
     */
    private $source;

    /**
     * @var bool
     */
    private $refresh;

    public function __construct()
    {
        $this->urls = null;
        $this->options = [];
        $this->urlManager = null;
        $this->source = [];
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options = [])
    {
        $this->refresh = true;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function setUrlManager(UrlManagerInterface $urlManager = null)
    {
        $this->refresh = true;
        $this->urlManager = $urlManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setSources(array $sources = [])
    {
        $this->refresh = true;
        $this->source = $sources;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrls()
    {
        if (is_null($this->urls) || $this->refresh === true) {
            $this->urls = array();
            $this->refresh = false;
            foreach ($this->options['images'] as &$image) {
                $this->urls[] = !is_null($this->urlManager)
                    ? $this->urlManager->url($this->options['output_dir']
                        . DIRECTORY_SEPARATOR . $this->filename($image))
                    : $this->source[$image['index']];
            }
        }

        return $this->urls;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
    private function compareImageData(array &$imageData, array &$image)
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
}
