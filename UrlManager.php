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

use vSymfo\Component\Document\Interfaces\UrlManagerInterface;

/**
 * Zarządzanie adresami URL na potrzeby dokumentu
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
class UrlManager implements UrlManagerInterface
{
    /**
     * np. katalog: /strona
     * bez shasha na końcu
     * 
     * @var string
     */
    private $baseurl = '';

    /**
     * Domena razem z protokołem np. http://static.mydomain.net
     * w większości przypadków ma być puste
     * bez shasha na końcu
     * 
     * @var string
     */
    private $domainPath = '';

    /**
     * Czy wersjonować zasób
     * 
     * @var bool
     */
    private $versioning = false;

    /**
     * Czy używać timestamp do wersjonowania
     * 
     * @var bool
     */
    private $verTimestamp = false;

    /**
     * Wersja zasobu
     * 
     * @var float
     */
    private $version = 1;

    /**
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        if (isset($options['baseurl']) && is_string($options['baseurl']))
            $this->setBaseUrl($options['baseurl']);
    }

    /**
     * Ustaw ścieżkę bazową
     * 
     * @param string $url
     */
    public function setBaseUrl($url)
    {
        $this->baseurl = is_string($url) ? preg_replace("/\/$/", "", $url) : '';
    }

    /**
     * @param $path
     */
    public function setDomainPath($path)
    {
        if(!filter_var($path, FILTER_VALIDATE_URL)) {
            $path = 'http://' . $path;
        }

        if (is_string($path) && !empty($path)) {
            $url = parse_url($path);
            $this->domainPath = isset($url['scheme']) ? $url['scheme'] . '://' : '';
            $this->domainPath .= preg_replace("/\/$/", "", isset($url['host']) ? $url['host'] : '');
        } else {
            $this->domainPath = '';
        }
    }

    /**
     * Pobierz URL
     * 
     * @param string $path
     * @param bool $addBaseUrl
     * @param bool $checkBaseUrl
     * 
     * @return string
     * 
     * @throws \InvalidArgumentException
     */
    public function url($path, $addBaseUrl = true, $checkBaseUrl = false)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('invalid path');
        }

        if (!empty($path)) {
            $url = parse_url(str_replace('&amp;', '&', $path));
            $parsedUrl = str_replace('&amp;', '&', $path);
            $scheme = trim(isset($url['scheme']) ? $url['scheme'] : '');
            $domain = trim(isset($url['host']) ? $url['host'] : '');

            // nazwa hosta
            if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
                $srvName =  $_SERVER['HTTP_HOST'];
            } else {
                $srvName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
            }

            // zewnętrzny zasób
            if (!empty($scheme) && !empty($domain)
                && $domain !== $srvName
                && $domain !== 'www.' . $srvName
            ) {
                return $parsedUrl;
            } else { // lokalny zasób
                // wersjonowanie
                if ($this->versioning) {
                    if ($this->verTimestamp) {
                        $parsedUrl = http_build_url($parsedUrl,
                            array(
                                'query' => 'version=' . time()
                            ), HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT
                        );
                    } else {
                        $parsedUrl = http_build_url($parsedUrl,
                            array(
                                'query' => 'version=' . $this->version
                            ), HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT
                        );
                    }
                }

                $tmpUrl = parse_url($parsedUrl);
                $q = trim(isset($tmpUrl['query']) ? $tmpUrl['query'] : '');
                $u = trim(isset($tmpUrl['path']) ? $tmpUrl['path'] : '');
                $u .= !empty($q) ? '?'.$q  : '';
                // slash zawsze na początku
                $u = !preg_match('/^\//', $u) ? '/' . $u : $u;
                if ($addBaseUrl && !$checkBaseUrl) {
                    // dodaj baseurl zawsze
                    $u = $this->baseurl . $u;
                } else if ($addBaseUrl && $checkBaseUrl) {
                    // dodaj baseurl jeśli nie ma
                    $u = !preg_match('/^' . str_replace('/', '\/', $this->baseurl) . '/', $u) ? $this->baseurl . $u : $u;
                }

                return $this->domainPath . $u;
            }
        }

        return $this->domainPath;
    }

    /**
     * Ustaw wersjonowanie
     * 
     * @param bool $enable
     * @param int $v
     * @param bool $timestamp
     */
    public function setVersioning($enable, $v = 1, $timestamp = false)
    {
        $this->versioning = (bool)$enable;
        $this->verTimestamp = (bool)$timestamp;
        $this->version = (float)$v;
    }
}
