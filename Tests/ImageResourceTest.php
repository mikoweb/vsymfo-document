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
use vSymfo\Component\Document\Resources\ImageResource;
use Imagine\Image\ImageInterface;
use Imagine\Gd\Imagine;

class ImageResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testTest()
    {
        $image = new ImageResource('Ford Mustang 1972',
            array(
                '/images/1972_ford_mustang-wide.jpg',
                '/images/1972_ford_mustang-mini.jpg'
            ),
            array(
                'root_dir' => __DIR__ . '/tmp',
                'output_dir' => '/images/test',
                'images' => array(
                    array(
                        'index' => 0,
                        'suffix' => 'test',
                        'width' => 1000,
                        'height' => 800,
                        'format' => 'jpg'
                    ),
                    array(
                        'index' => 1,
                        'suffix' => 'ok',
                        'width' => 300,
                        'height' => 150,
                        'format' => 'png'
                    )
                )
            )
        );
        $url = new UrlManager();
        $url->setBaseUrl('/tmp/');
        $image->setUrlManager($url);
        $image->save();

        $imagine = new Imagine();
        $size = $imagine->open(__DIR__ . '/tmp/images/test/1972_ford_mustang-mini_ok_300x150.png')->getSize();
        $this->assertEquals(300, $size->getWidth());
        $this->assertEquals(150, $size->getHeight());

        $size = $imagine->open(__DIR__ . '/tmp/images/test/1972_ford_mustang-wide_test_1000x800.jpg')->getSize();
        $this->assertEquals(1000, $size->getWidth());
        $this->assertEquals(800, $size->getHeight());

        $image->cleanup();
        $this->assertEquals(0, count(glob(__DIR__ . "/tmp/images/test/*")));

        $urls = $image->getUrl();
        $this->assertEquals('/tmp/images/test/1972_ford_mustang-wide_test_1000x800.jpg', $urls[0]);
        $this->assertEquals('/tmp/images/test/1972_ford_mustang-mini_ok_300x150.png', $urls[1]);
    }
}
