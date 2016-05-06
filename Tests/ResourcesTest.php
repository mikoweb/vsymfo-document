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
use vSymfo\Component\Document\Resources\JavaScriptCombineFiles;
use vSymfo\Component\Document\Resources\JavaScriptResourceManager;
use vSymfo\Component\Document\Resources\JavaScriptResource;
use vSymfo\Component\Document\Resources\StyleSheetCombineFiles;
use vSymfo\Component\Document\Resources\StyleSheetResourceManager;
use vSymfo\Component\Document\Resources\StyleSheetResource;
use vSymfo\Component\Document\ResourceGroups;
use vSymfo\Core\File\CombineFilesCacheDB;
use Symfony\Component\Config\FileLocator;

class ResourcesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * testowanie grup
     */
    public function testGroups()
    {
        $groups = new ResourceGroups();
        $groups->addGroup('test2', array('test'));
        $groups->addGroup('test');
        $all = $groups->getAll();
        $this->assertArrayHasKey('groups', $all);
        $this->assertArrayHasKey('unknown', $all);
        $this->assertCount(2, $all['groups']);
        $this->assertCount(0, $all['unknown']);
        $this->assertEquals('test', $all['groups'][0]['name']);
        $this->assertEquals('test2', $all['groups'][1]['name']);
        $this->assertEquals('test', $all['groups'][1]['value']['dependencies'][0]);
        $this->assertCount(0, $all['groups'][1]['value']['resources']);
    }

    public function testJavaScript()
    {
        $groups = new ResourceGroups();
        $groups->addGroup('foo');
        $groups->addGroup('bar');

        $res = new JavaScriptResourceManager($groups,
            function(JavaScriptResource $res) {
                $combine = new JavaScriptCombineFiles();
                $combine->setInputDir(__DIR__)
                    ->setOutputDir(__DIR__ . '/tmp/cache')
                    ->setOutputBaseUrl('/tmp/cache')
                    ->setOutputForceRefresh(true)
                    ->setOutputLifeTime(0)
                    ->setOutputStrategy('manual')
                    ->setCacheDb(
                        CombineFilesCacheDB::openFile(
                            __DIR__ . '/tmp/cache/js.db'
                        )
                    )
                ;

                $url = new UrlManager();
                $res->setCombineObject($combine);
                $res->setUrlManager($url);
            }
        );

        $this->assertSame($groups, $res->getGroups());

        $res->add(
            new JavaScriptResource('foo_and_bar',
                array('/tmp/js/foo.js', '/tmp/js/bar.js'),
                array('combine' => true)
            ), 'foo'
        );

        $res->add(
            new JavaScriptResource('bar_and_foo',
                array('/tmp/js/bar.js', '/tmp/js/foo.js'),
                array('combine' => true)
            ), 'bar'
        );

        $this->assertTrue(file_exists(__DIR__ . '/tmp/cache/js.db'));
        foreach ($res->resources() as $js) {
            foreach ($js->getUrl() as $url) {
                $this->assertTrue(file_exists(__DIR__ . $url));
            }
        }

        $this->assertNotEmpty($res->render('json'));
        $this->assertNotEmpty($res->render('html'));
    }

    public function testStyleSheet()
    {
        $groups = new ResourceGroups();
        $groups->addGroup('bootstrap');

        $res = new StyleSheetResourceManager($groups,
            function(StyleSheetResource $res) {
                $combine = new StyleSheetCombineFiles();
                $combine->setInputDir(__DIR__)
                    ->setOutputDir(__DIR__ . '/tmp/cache')
                    ->setOutputBaseUrl('/tmp/cache')
                    ->setOutputForceRefresh(true)
                    ->setOutputLifeTime(0)
                    ->setOutputStrategy('manual')
                    ->setCacheDb(
                        CombineFilesCacheDB::openFile(
                            __DIR__ . '/tmp/cache/less.db'
                        )
                    )
                    ->setLessImportDirs(array(__DIR__ . '/tmp/cache/less'))
                    ->setLessVariables(array('foo' => 'bar'))
                    ->setScssImportDirs(array(
                        __DIR__,
                        __DIR__ . '/tmp/scss'
                    ))
                    ->setScssVariables(array('foo' => 'bar'))
                ;

                $url = new UrlManager();
                $res->setCombineObject($combine);
                $res->setUrlManager($url);
            }
        );

        $this->assertSame($groups, $res->getGroups());

        $res->add(
            new StyleSheetResource('foo',
                array('/tmp/less/bootstrap.less', '/tmp/scss/test.scss'),
                array('combine' => true)
            ), 'bootstrap'
        );

        $this->assertTrue(file_exists(__DIR__ . '/tmp/cache/less.db'));
        foreach ($res->resources() as $css) {
            foreach ($css->getUrl() as $url) {
                $this->assertTrue(file_exists(__DIR__ . $url));
            }
        }

        $this->assertNotEmpty($res->render('html'));
    }

    public function testGrunt()
    {
        $groups = new ResourceGroups();
        $groups->addGroup('grunt_scss');

        $res = new StyleSheetResourceManager($groups,
            function(StyleSheetResource $res) {
                $combine = new StyleSheetCombineFiles();
                $combine->setInputDir(__DIR__)
                    ->setOutputDir(__DIR__ . '/tmp/cache')
                    ->setOutputBaseUrl('/tmp/cache')
                    ->setOutputForceRefresh(true)
                    ->setOutputLifeTime(0)
                    ->setOutputStrategy('manual')
                    ->setCacheDb(CombineFilesCacheDB::openFile(__DIR__ . '/tmp/cache/grunt.db'))
                    ->setScssImportDirs(array(
                        __DIR__ . '/tmp/scss/grunt_import',
                        __DIR__ . '/tmp/scss/grunt',
                        __DIR__,
                    ))
                    ->setScssVariables(array(
                        'path-background' => '/images/bg.png',
                        'foo' => 'red'
                    ))
                ;

                $url = new UrlManager();
                $res->setCombineObject($combine);
                $res->setUrlManager($url);
            }
        );

        $res->add(
            new StyleSheetResource('style',
                array('/tmp/scss/style.scss,grunt'),
                array('combine' => true)
            ), 'grunt_scss'
        );

        $this->assertNotEmpty($res->render('html'));
    }
}
