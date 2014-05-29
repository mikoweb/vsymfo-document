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

namespace vSymfo\Component\Document\Resources\Interfaces;

use vSymfo\Component\Document\Interfaces\UrlManagerInterface;

/**
 * pojedyńczy zasób
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources_Interfaces
 */
interface ResourceInterface
{
    /**
     * @param string name
     * @param array $source
     * @param array $options
     */
    public function __construct($name, array $source, array $options = array());

    /**
     * Filtrowanie źródeł
     * @param string $type
     * @param array $args
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     */
    public function filter($type, array $args);

    /**
     * Zwraca nazwę zasobu
     * @return string
     */
    public function getName();

    /**
     * Ustawia nazwę zasobu
     * @param string $name
     */
    public function setName($name);

    /**
     * Zwraca listę źródeł
     * @return array
     */
    public function getSources();

    /**
     * Zwraca tablicę adresów URL do zasobów
     * @return array
     */
    public function getUrl();

    /**
     * @param UrlManagerInterface $urlManager;
     */
    public function setUrlManager(UrlManagerInterface $urlManager);
}