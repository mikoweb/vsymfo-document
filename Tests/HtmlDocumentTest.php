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

use vSymfo\Component\Document\Format\HtmlDocument;
use vSymfo\Component\Document\UrlManager;
use vSymfo\Component\Document\Resources\JavaScriptCombineFiles;
use vSymfo\Component\Document\Resources\JavaScriptResource;
use vSymfo\Component\Document\Resources\StyleSheetCombineFiles;
use vSymfo\Component\Document\Resources\StyleSheetResource;
use vSymfo\Component\Document\FileLoader\JavaScriptResourcesLoader;
use vSymfo\Component\Document\FileLoader\StyleSheetResourcesLoader;
use vSymfo\Core\File\CombineFilesCacheDB;
use Symfony\Component\Config\FileLocator;

class HtmlDocumentTest extends \PHPUnit_Framework_TestCase
{
    protected $doc;

    public function __construct()
    {
        $this->doc = new HtmlDocument();
        $this->doc->title('Sample Page');
        $this->doc->body('<body><p>lorem ipsum</p></body>');
    }

    /**
     * test loadera zasobów
     */
    public function testResourcesLoader()
    {
        $manager = $this->doc->resources('javascript');
        $manager->setOnAdd("testAdd", function(JavaScriptResource $res) {
            $combine = new JavaScriptCombineFiles();
            $combine->setInputDir(__DIR__)
                ->setOutputDir(__DIR__ . '/tmp/cache')
                ->setOutputBaseUrl('/tmp/cache')
                ->setOutputForceRefresh(true)
                ->setOutputLifeTime(0)
                ->setOutputStrategy('manual')
                ->setCacheDb(
                    CombineFilesCacheDB::openFile(
                        __DIR__ . '/tmp/cache/html_js.db'
                    )
                )
            ;

            $res->setCombineObject($combine);
            $res->setUrlManager(new UrlManager());
        });
        $manager->chooseOnAdd("testAdd");

        $locator = new FileLocator(__DIR__ . '/tmp/config');
        $loader = new JavaScriptResourcesLoader($locator, array(
            'baseurl' => '/tmp',
            'combine' => true,
            'resources' => $manager,
            'cache_dir' => __DIR__ . '/tmp/cache',
            'cache_refresh' => true
        ));

        $loader->load('html_resources.yml', 'framework');
        $loader->load('html_resources.yml', 'core');

        $manager = $this->doc->resources('stylesheet');
        $manager->setOnAdd("styleTestAdd", function(StyleSheetResource $res) {
            $combine = new StyleSheetCombineFiles();
            $combine->setInputDir(__DIR__)
                ->setOutputDir(__DIR__ . '/tmp/cache')
                ->setOutputBaseUrl('/tmp/cache')
                ->setOutputForceRefresh(true)
                ->setOutputLifeTime(0)
                ->setOutputStrategy('manual')
                ->setCacheDb(
                    CombineFilesCacheDB::openFile(
                        __DIR__ . '/tmp/cache/html_less.db'
                    )
                )
                ->setLessImportDirs(array(__DIR__ . '/tmp/cache/less'))
                ->setLessGlobasls(array('foo' => 'bar'))
            ;

            $res->setCombineObject($combine);
            $res->setUrlManager(new UrlManager());
        });
        $manager->chooseOnAdd("styleTestAdd");
        $manager->setOnAdd("hoho", function(StyleSheetResource $res) {});
        $manager->chooseOnAdd("hoho");
        $manager->chooseOnAdd("styleTestAdd");

        $locator = new FileLocator(__DIR__ . '/tmp/config');
        $loader = new StyleSheetResourcesLoader($locator, array(
            'baseurl' => '/tmp',
            'combine' => true,
            'resources' => $manager,
            'cache_dir' => __DIR__ . '/tmp/cache',
            'cache_refresh' => true
        ));

        $loader->load('html_resources.yml', 'theme');

        //var_dump($this->doc->render());
        $dom = new DOMDocument();
        $dom->loadHTML($this->doc->render());
        $xpath = new DOMXPath($dom);

        $this->assertEquals('Sample Page', $xpath->query('head/title')->item(0)->nodeValue);
        $this->assertEquals('/tmp/cache/theme.css', $xpath->query('head/link[@rel="stylesheet"]')->item(0)->getAttribute('href'));
        $this->assertEquals('/tmp/cache/framework.js', $xpath->query('head/script')->item(0)->getAttribute('src'));
        $this->assertEquals('/tmp/cache/core.js', $xpath->query('head/script')->item(1)->getAttribute('src'));
        $this->assertEquals('lorem ipsum', $xpath->query('body/p')->item(0)->nodeValue);

        $this->assertTrue(file_exists(__DIR__ . '/tmp/cache/html_js.db'));
        $this->assertTrue(file_exists(__DIR__ . '/tmp/cache/html_less.db'));
        $this->assertTrue(file_exists(__DIR__ . '/tmp/cache/core.js'));
        $this->assertTrue(file_exists(__DIR__ . '/tmp/cache/framework.js'));
        $this->assertTrue(file_exists(__DIR__ . '/tmp/cache/theme.css'));
    }
}
