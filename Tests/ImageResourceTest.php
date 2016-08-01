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
use vSymfo\Component\Document\Resources\ImageResourceManager;
use vSymfo\Component\Document\ResourceGroups;
use vSymfo\Component\Document\Resources\Storage\ImagineImagesStorage;
use Imagine\Image\ImageInterface;
use Imagine\Gd\Imagine;

class ImageResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testRes()
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

        $imageStorage = new ImagineImagesStorage();
        $image->setUrlManager($url);
        $image->setImagesStorage($imageStorage);
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

    public function testManager()
    {
        $res = new ImageResourceManager(
            new ResourceGroups(),
            function(ImageResource $res) {
                $url = new UrlManager();
                $url->setBaseUrl('/tmp/');
                $res->setUrlManager($url);
                $imageStorage = new ImagineImagesStorage();
                $res->setImagesStorage($imageStorage);
            }
        );

        $res->add(new ImageResource('Ford Mustang 1972',
                array(
                    '/images/1972_ford_mustang-wide.jpg',
                    '/images/1972_ford_mustang-mini.jpg'
                ),
                array(
                    'root_dir' => __DIR__ . '/tmp',
                    'output_dir' => '/images/test',
                    'sizes' => '100vw, (min-width: 40em) 80vw',
                    'media' => array(
                        '(min-width: 1000px)',
                        '(min-width: 800px)',
                    ),
                    'attr' => array(
                        'class' => 'test',
                        'data-test' => 'ok'
                    ),
                    'images' => array(
                        array(
                            'index' => 0,
                            'suffix' => 'test',
                            'width' => 1000,
                            'height' => 800,
                            'format' => 'jpg',
                            'srcset_x' => 2,
                            'media_index' => 0
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
            )
        );

        $input = $res->render('html_picture');
        libxml_use_internal_errors(true);
        foreach ($input as $code) {
            $dom = new DOMDocument();
            $dom->loadHTML($code);
            $xpath = new DOMXPath($dom);

            $this->assertEquals(1, $xpath->query('body/picture')->length);
            $el = $xpath->query('body/picture')->item(0);
            $this->assertEquals('test', $el->getAttribute('class'));

            $el = $xpath->query('/descendant::img')->item(0);
            $this->assertEquals('/tmp/images/test/1972_ford_mustang-wide_test_1000x800.jpg', $el->getAttribute('src'));

            $el = $xpath->query('/descendant::source');
            $this->assertEquals('(min-width: 1000px)', $el->item(0)->getAttribute('media'));
            $this->assertEquals('/tmp/images/test/1972_ford_mustang-wide_test_1000x800.jpg 2x', $el->item(0)->getAttribute('srcset'));
            $this->assertEquals('', $el->item(1)->getAttribute('media'));
            $this->assertEquals('/tmp/images/test/1972_ford_mustang-mini_ok_300x150.png', $el->item(1)->getAttribute('srcset'));
        }
        libxml_use_internal_errors(false);

        $input = $res->render('html_img');
        foreach ($input as $code) {
            $dom = new DOMDocument();
            $dom->loadHTML($code);
            $xpath = new DOMXPath($dom);

            $img = $xpath->query('body/img');
            $this->assertEquals(1, $img->length);
            $img = $img->item(0);
            $this->assertEquals('Ford Mustang 1972', $img->getAttribute('alt'));
            $this->assertEquals('100vw, (min-width: 40em) 80vw', $img->getAttribute('sizes'));
            $this->assertEquals('/tmp/images/test/1972_ford_mustang-wide_test_1000x800.jpg', $img->getAttribute('src'));
            $this->assertEquals('/tmp/images/test/1972_ford_mustang-mini_ok_300x150.png', $img->getAttribute('srcset'));
        }
    }
}
