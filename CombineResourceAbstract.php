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

namespace vSymfo\Component\Document;

use vSymfo\Component\Document\Resources\Interfaces\CombineResourceInterface;
use vSymfo\Core\File\Interfaces\CombineFilesInterface;

/**
 * Pojedynczy złączony zasób
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
abstract class CombineResourceAbstract extends ResourceAbstract implements CombineResourceInterface
{
    /**
     * Czy kompilować?
     * @var bool
     */
    protected $isCombine = false;

    /**
     * Obiekt do złączania plików
     * @var null|CombineFilesInterface
     */
    protected $combine = null;

    /**
     * @param string name
     * @param array $source
     * @param array $options
     */
    public function __construct($name, array $source, array $options = array())
    {
        $options = $this->defaults($options);
        parent::__construct($name, $source, $options);
        $this->isCombine = (bool)$options['combine'];
    }

    /**
     * domyślne opcje konstruktora
     * @param array $options
     * @return array
     */
    protected function defaults(array $options)
    {
        return array_merge(
            array(
                'combine' => false,
            )
            , $options
        );
    }

    /**
     * Ustawia nazwę zasobu
     * @param string $name
     * @throws \InvalidArgumentException
     */
    public function setName($name)
    {
        if (is_string($name) && preg_match('/^[a-zA-Z1-9_-]*$/', $name)) {
            $this->name = $name;
        } else {
            throw new \InvalidArgumentException('Invalid resource name: '.(string)$name);
        }

        if (!empty($this->combine)) {
            $this->combine->setOutputFileName($name);
        }
    }

    /**
     * Ustawia obiekt służący do złączania plików
     * @param CombineFilesInterface $combine
     * @return void
     */
    public function setCombineObject(CombineFilesInterface $combine)
    {
        $this->combine = $combine;
    }

    /**
     * @return false|CombineFilesInterface
     */
    public function getCombineObject()
    {
        return $this->combine === null ? false : $this->combine;
    }

    /**
     * Podaj tablicę adresów URL do zasobów
     * @return array
     */
    public function getUrl()
    {
        if (!$this->isCombine || empty($this->combine)) {
            return parent::getUrl();
        }

        // generuj listę tylko jeden raz
        if ($this->urls === null) {
            $this->combine->combine();
            $this->urls = array(
                $this->urlManager->url($this->combine->getPath(true), false)
            );
        }

        return $this->urls;
    }
}
