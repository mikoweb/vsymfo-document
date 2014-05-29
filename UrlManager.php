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

use Purl\Url;
use vSymfo\Component\Document\Interfaces\UrlManagerInterface;

/**
 * Zarządzanie adresami URL na potrzeby dokumentu
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
class UrlManager implements UrlManagerInterface
{
    /**
     * np. katalog: /strona
     * bez shasha na końcu
     * @var string
     */
    private $baseurl = '';

    /**
     * domena razem z protokołem np. http://static.mydomain.net
     * w większości przypadków ma być puste
     * bez shasha na końcu
     * @var string
     */
    private $domainPath = '';

    /**
     * czy wersjonować zasób
     * @var bool
     */
    private $versioning = false;

    /**
     * czy używać timestamp do wersjonowania
     * @var bool
     */
    private $verTimestamp = false;

    /**
     * wersja zasobu
     * @var float
     */
    private $version = 1;

    /**
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        if (isset($options['baseurl']) && is_string($options['baseurl']))
            $this->setBaseurl($options['baseurl']);
    }

    /**
     * ustaw ścieżkę bazową
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
        if (is_string($path) && !empty($path)) {
            $url = new Url($path);
            $url->query = '';
            $url->path = '';
            $this->domainPath = preg_replace("/\/$/", "", $url->getUrl());
        } else {
            $this->domainPath = '';
        }
    }

    /**
     * zapodaj obrobiony adres URL
     * @param string $path
     * @param bool $addBaseUrl
     * @param bool $checkBaseUrl
     * @return string
     * @throws \InvalidArgumentException
     */
    public function url($path, $addBaseUrl = true, $checkBaseUrl = false)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('invalid path');
        }

        if (!empty($path)) {
            $url = new Url(str_replace('&amp;', '&', $path));
            $scheme = trim($url->get('scheme'));
            $domain = trim($url->registerableDomain);
            $subdomain = trim($url->subdomain);
            $full_domain = !empty($subdomain) ? $subdomain . '.' . $domain : $domain;

            // zewnętrzny zasób
            $srvName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
            if (!empty($scheme) && !empty($full_domain)
                && $domain !== 'index.php'
                && $full_domain !== $srvName
                && $full_domain !== 'www.' . $srvName
            ) {
                return $url->getUrl();
            } else { // lokalny zasób
                // wersjonowanie
                if ($this->versioning)
                    $this->verTimestamp ? $url->query->set('version', time())
                        : $url->query->set('version', $this->version);

                $q = trim($url->getQuery());
                $u = trim($url->getPath());
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
     * ustaw wersjonowanie
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
