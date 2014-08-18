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

use vSymfo\Component\Document\Format\RssDocument;

class RssDocumentTest extends \PHPUnit_Framework_TestCase
{
    protected $doc;

    public function __construct()
    {
        $this->doc = new RssDocument();
    }

    public function testElements()
    {
        $this->doc->title('Sample RSS Feed');
        $this->doc->body('<lorem>Lorem ipsum</lorem>');
        $this->assertEquals('<lorem>Lorem ipsum</lorem>', $this->doc->body());
        $this->doc->body('');
        $this->assertEmpty($this->doc->body());

        $this->doc->link('');
        $this->assertEmpty($this->doc->link());
        $this->doc->link("http://www.google.pl");
        $this->assertEquals("http://www.google.pl", $this->doc->link());

        $this->doc->language('pl-pl');
        $this->assertEquals('pl-pl', $this->doc->language());
        $this->doc->language('');
        $this->assertEmpty($this->doc->language());

        $this->doc->copyright('Copyright 2002, Spartanburg Herald-Journal');
        $this->assertEquals('Copyright 2002, Spartanburg Herald-Journal', $this->doc->copyright());
        $this->doc->copyright('');
        $this->assertEmpty($this->doc->copyright());

        $this->doc->managingEditor('test@john.doe');
        $this->assertEquals('test@john.doe', $this->doc->managingEditor());
        $this->doc->managingEditor('');
        $this->assertEmpty($this->doc->managingEditor());

        $this->doc->webMaster('webmaster@john.doe');
        $this->assertEquals('webmaster@john.doe', $this->doc->webMaster());
        $this->doc->webMaster('');
        $this->assertEmpty($this->doc->webMaster());

        $this->doc->pubDate('Wed, 02 Oct 2002 15:00:00 +0200');
        $this->assertEquals('Wed, 02 Oct 2002 15:00:00 +0200', $this->doc->pubDate());
        $this->assertEquals('Wed, 02 Oct 2002 15:00:00 +0200', $this->doc->createdDate()->format(\DateTime::RSS));
        $this->doc->createdDate('2011-10-31 12:40:20');
        $this->assertEquals('Mon, 31 Oct 2011 12:40:20 +0100', $this->doc->pubDate());

        $this->doc->lastBuildDate('Wed, 02 Oct 2002 15:45:00 +0200');
        $this->assertEquals('Wed, 02 Oct 2002 15:45:00 +0200', $this->doc->lastBuildDate());
        $this->assertEquals('Wed, 02 Oct 2002 15:45:00 +0200', $this->doc->lastModified()->format(\DateTime::RSS));
        $this->doc->lastModified('2011-10-31 12:40:20');
        $this->assertEquals('Mon, 31 Oct 2011 12:40:20 +0100', $this->doc->lastBuildDate());

        $this->doc->category('Lorem Ipsum');
        $this->assertEquals('Lorem Ipsum', $this->doc->category());
        $this->doc->category('');
        $this->assertEmpty($this->doc->category());

        $this->doc->generator('vSymfo Document Component');
        $this->assertEquals('vSymfo Document Component', $this->doc->generator());
        $this->doc->generator('');
        $this->assertEmpty($this->doc->generator());

        $this->doc->ttl('60');
        $this->assertEquals('60', $this->doc->ttl());
        $this->doc->ttl('');
        $this->assertEmpty($this->doc->ttl());

        $this->doc->image(array(
                'url' => 'http://www.google.pl/image.png',
                'title' => 'Image Name',
                'link' => 'http://www.google.pl',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.',
                'width' => 50,
                'height' => 100
            ));
        $image = $this->doc->image();
        $this->assertEquals('http://www.google.pl/image.png', $image['url']);
        $this->assertEquals('Image Name', $image['title']);
        $this->assertEquals('http://www.google.pl', $image['link']);
        $this->assertEquals('Lorem ipsum dolor sit amet, consectetur adipisicing elit.', $image['description']);
        $this->assertEquals(50, $image['width']);
        $this->assertEquals(100, $image['height']);

        $this->doc->textInput(array(
                'title' => 'Text Input',
                'link' => 'http://www.google.pl',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.',
                'name' => 'Text Input'
            ));
        $textInput = $this->doc->textInput();
        $this->assertEquals('Text Input', $textInput['title']);
        $this->assertEquals('http://www.google.pl', $textInput['link']);
        $this->assertEquals('Lorem ipsum dolor sit amet, consectetur adipisicing elit.', $textInput['description']);
        $this->assertEquals('Text Input', $textInput['name']);

        $this->doc->skipHours(array(-1, 30, 2, 5, 6));
        $skipHours = $this->doc->skipHours();
        $this->assertEquals(2, $skipHours[0]);
        $this->assertEquals(5, $skipHours[1]);
        $this->assertEquals(6, $skipHours[2]);
        $this->doc->skipHours(array());
        $this->assertEmpty($this->doc->skipHours());

        $this->doc->skipDays(array('Monday', 'Tuesday'));
        $skipDays = $this->doc->skipDays();
        $this->assertEquals('Monday', $skipDays[0]);
        $this->assertEquals('Tuesday', $skipDays[1]);
        $this->doc->skipDays(array());
        $this->assertEmpty($this->doc->skipDays());

        $this->doc->cloud(array(
                'domain' => 'rpc.sys.com',
                'port' => 80,
                'path' => '/RPC2',
                'registerProcedure' => 'pingMe',
                'protocol' => 'soap'
            ));
        $cloud = $this->doc->cloud();
        $this->assertEquals('rpc.sys.com', $cloud['domain']);
        $this->assertEquals(80, $cloud['port']);
        $this->assertEquals('/RPC2', $cloud['path']);
        $this->assertEquals('pingMe', $cloud['registerProcedure']);
        $this->assertEquals('soap', $cloud['protocol']);
    }
}
