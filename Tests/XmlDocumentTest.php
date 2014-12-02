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
    public function testProlog()
    {
        $doc = new XmlDocument();
        $prolog = $doc->element('prolog');
        $this->assertEquals('<?xml version="1.0" encoding="{{ encoding }}"?>', $prolog->render());
    }

    public function testRoot()
    {
        $doc = new XmlDocument();
        $root = $doc->element('root');
        $root->attr('test', 'ok');
        $this->assertEquals('<root test="ok"></root>', $root->render());
        $doc->renameRoot('newroot');
        $root = $doc->element('root');
        $this->assertEquals('<newroot test="ok"></newroot>', $root->render());
    }

    public function testBody()
    {
        $doc = new XmlDocument();
        $doc->renameRoot('myroot');
        $root = $doc->element('root');
        $root->attr('test', 'ok');
        $equalCode = '<?xml version="1.0" encoding="UTF-8"?>' .  PHP_EOL;
        $equalCode .= '<myroot test="ok">' .  PHP_EOL;
        $equalCode .= '</myroot>';

        $this->assertEquals($equalCode, $doc->render());
    }
}
