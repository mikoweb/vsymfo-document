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

use vSymfo\Component\Document\Format\AtomDocument;

class AtomDocumentTest extends \PHPUnit_Framework_TestCase
{
    protected $doc;

    public function __construct()
    {
        $this->doc = new AtomDocument();
    }

    public function testElements()
    {
        $this->doc->title("Sample Atom Feed");
        $this->assertEquals("Sample Atom Feed", $this->doc->title());

        $this->doc->subtitle("Feed Subtitle");
        $this->assertEquals("Feed Subtitle", $this->doc->subtitle());

        $this->doc->body('');
        $this->assertEmpty($this->doc->body());
        $this->doc->body('<lorem>Lorem ipsum</lorem>');
        $this->assertEquals('<lorem>Lorem ipsum</lorem>', $this->doc->body());

        $this->doc->updated('2003-12-13T18:30:02Z');
        $this->assertEquals('2003-12-13T18:30:02+00:00', $this->doc->updated());
        $this->assertEquals('2003-12-13T18:30:02+00:00', $this->doc->lastModified()->format(\DateTime::ATOM));
        $this->doc->lastModified('2014-08-26T11:32:02Z');
        $this->assertEquals('2014-08-26T11:32:02+00:00', $this->doc->updated());

        $this->doc->id('Nieprawidłowy format linku');
        $this->assertEmpty($this->doc->id());
        $this->doc->id('http://www.google.pl/myfeed');
        $this->assertEquals('http://www.google.pl/myfeed', $this->doc->id());

        $result = $this->doc->author('John Doe');
        $this->assertEquals('John Doe', $result['name']);
        $result = $this->doc->author(array(
                'name' => 'New John Doe',
                'email' => 'john@doe.com',
                'uri' => 'http://www.google.pl'
            ));
        $this->assertEquals('New John Doe', $result['name']);
        $this->assertEquals('john@doe.com', $result['email']);
        $this->assertEquals('http://www.google.pl', $result['uri']);

        $result = $this->doc->authorUrl('http://www.john.doe');
        $this->assertEquals('http://www.john.doe', $result);
        $result = $this->doc->author();
        $this->assertEquals('http://www.john.doe', $result['uri']);

        $result = $this->doc->linkSelf('http://www.google.pl/myfeed');
        $this->assertEquals('http://www.google.pl/myfeed', $result);

        $this->doc->icon('/path/to/icon.png');
        $this->assertEquals('/path/to/icon.png', $this->doc->icon());

        $this->doc->logo('/path/to/logo.png');
        $this->assertEquals('/path/to/logo.png', $this->doc->logo());

        $this->doc->rights('Prawa autorskie');
        $this->assertEquals('Prawa autorskie', $this->doc->rights());

        $this->doc->addCategory(array(
                'term' => 'my-category'
            ));
        $this->doc->addCategory(array(
                'term' => 'my-category',
                'label' => 'My Category',
                'scheme' => 'http://john.doe/my-category'
            ));
        $this->doc->addCategory(array(
                'term' => 'test'
            ));
        $this->doc->removeCategory('test');

        $this->doc->addContributor(array(
                'name' => 'Jimmy Potatos'
            ));
        $this->doc->addContributor(array(
                'name' => 'Jimmy Potatos',
                'email' => 'jimmy@potatos.com',
                'uri' => 'http://jimmy.potatos.com'
            ));
        $this->doc->addContributor(array(
                'name' => 'test'
            ));
        $this->doc->removeContributor('test');
    }
}
