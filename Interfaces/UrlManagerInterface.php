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

namespace vSymfo\Component\Document\Interfaces;

/**
 * zarządzanie URL'em
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Interfaces
 */
interface UrlManagerInterface
{
    /**
     * zapodaj obrobiony adres URL
     * @param string $path
     * @param bool $addBaseUrl
     * @param bool $checkBaseUrl
     * @return string
     * @throws \InvalidArgumentException
     */
    public function url($path, $addBaseUrl = true, $checkBaseUrl = false);

    /**
     * ustaw ścieżkę bazową
     * @param string $url
     */
    public function setBaseUrl($url);

    /**
     * @param $path
     */
    public function setDomainPath($path);

    /**
     * ustaw wersjonowanie
     * @param bool $enable
     * @param int $v
     * @param bool $timestamp
     */
    public function setVersioning($enable, $v = 1, $timestamp = false);
}
