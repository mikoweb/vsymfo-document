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

use vSymfo\Component\Document\UrlManager;

class UrlManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $url = new UrlManager(array(
            'baseurl' => '/root'
        ));
        $this->assertEquals('/root/test.html', $url->url('/test.html'));
    }

    public function testDomain()
    {
        $url = new UrlManager();
        $url->setDomainPath('www.google.pl');
        $this->assertEquals('http://www.google.pl/test.html', $url->url('/test.html'));
        $url->setDomainPath('http://www.google.pl');
        $this->assertEquals('http://www.google.pl/test.html', $url->url('/test.html'));
        $url->setDomainPath('https://www.google.pl');
        $this->assertEquals('https://www.google.pl/test.html', $url->url('/test.html'));
        $url->setDomainPath('ftp://google.pl');
        $this->assertEquals('ftp://google.pl/test.html', $url->url('/test.html'));
        $url->setDomainPath('http://google.pl');
        $url->setBaseUrl('/root');
        $this->assertEquals('http://google.pl/root/test.html', $url->url('/test.html'));
    }

    public function testVersioning()
    {
        $url = new UrlManager();
        $url->setBaseUrl('/root');
        $url->setVersioning(true, '2.1');
        $this->assertEquals('/root/test.html?version=2.1', $url->url('/test.html'));
    }
}
