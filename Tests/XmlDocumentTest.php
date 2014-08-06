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

use vSymfo\Component\Document\Format\XmlDocument;

class XmlDocumentTest extends \PHPUnit_Framework_TestCase
{
    protected $doc;

    public function __construct()
    {
        $this->doc = new XmlDocument();
    }

    public function testProlog()
    {
        $prolog = $this->doc->element('prolog');
        $this->assertEquals('<?xml version="1.0" encoding="{{ encoding }}"?>', $prolog->render());
    }

    public function testRoot()
    {
        $root = $this->doc->element('root');
        $root->attr('test', 'ok');
        $this->assertEquals('<root test="ok"></root>', $root->render());
        $this->doc->renameRoot('newroot');
        $root = $this->doc->element('root');
        $this->assertEquals('<newroot test="ok"></newroot>', $root->render());
    }
}
