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

use vSymfo\Component\Document\Element\FaviconElement;

class FaviconElementTest extends \PHPUnit_Framework_TestCase
{
    public function testFavicon()
    {
        $favicon = new FaviconElement();
        $favicon->setBasePath('/path/to/favicon/');
        $favicon->setTileColor('#000000');
        $favicon->setFaviconTemplate('{{basepath}} {{tileColor}} {{basepath}} {{tileColor}}');
        $this->assertEquals($favicon->render(), '/path/to/favicon/ #000000 /path/to/favicon/ #000000');
        $favicon->enable(false);
        $this->assertEquals($favicon->render(), '');
        $favicon->enable(true);
        $favicon->setFaviconTemplate('<link rel="apple-touch-icon" sizes="57x57" href="{{basepath}}/apple-touch-icon-57x57.png"><meta name="msapplication-TileColor" content="{{tileColor}}">');
        $this->assertEquals($favicon->render(), '<link rel="apple-touch-icon" sizes="57x57" href="/path/to/favicon//apple-touch-icon-57x57.png"><meta name="msapplication-TileColor" content="#000000">');
    }
}
