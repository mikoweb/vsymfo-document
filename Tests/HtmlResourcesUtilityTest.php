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

use Symfony\Component\Config\FileLocator;
use vSymfo\Component\Document\Format\HtmlDocument;
use vSymfo\Component\Document\Utility\HtmlResourcesUtility;

class HtmlResourcesUtilityTest extends \PHPUnit_Framework_TestCase
{
    public function testUtility()
    {
        $doc = new HtmlDocument();
        $utility = new HtmlResourcesUtility(array(
                'cache_dir'      => __DIR__ . '/tmp/cache',
                'cache_refresh'  => true,
                'cache_lifetime' => 0,
                'web_dir'        => __DIR__,
                'web_url'        => '/',
                'web_cache_dir'  => __DIR__ . '/tmp/cache',
                'web_cache_url'  => '/tmp/cache',
                'less_import_dirs' => array(__DIR__ . '/tmp/less'),
                'less_globasls'    => array('foo' => 'bar')
            ));

        $utility->createResOnAdd($doc, "javascript", "default");
        $utility->createResOnAdd($doc, "stylesheet", "default");

        $doc->resources("javascript")->chooseOnAdd("default");
        $doc->resources("stylesheet")->chooseOnAdd("default");

        $locator = new FileLocator(__DIR__ . '/tmp/config');
        // loader javascript
        $loader = $utility->createResourcesLoader($doc, 'javascript', $locator, '/tmp');
        $loader->load('html_resources.yml', 'framework');
        $loader->load('html_resources.yml', 'core');

        // loader css
        $loader = $utility->createResourcesLoader($doc, 'stylesheet', $locator, '/tmp');
        $loader->load('html_resources.yml', 'theme');

        // testowanie czy na wyjsciu są prawidłowe znaczniki
        $dom = new DOMDocument();
        $dom->loadHTML($doc->render());
        $xpath = new DOMXPath($dom);

        $this->assertEquals('/tmp/cache/theme.css', $xpath->query('head/link[@rel="stylesheet"]')->item(0)->getAttribute('href'));
        $this->assertEquals('/tmp/cache/framework.js', $xpath->query('head/script')->item(0)->getAttribute('src'));
        $this->assertEquals('/tmp/cache/core.js', $xpath->query('head/script')->item(1)->getAttribute('src'));
    }
}
