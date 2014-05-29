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

use vSymfo\Component\Document\Element\HtmlElement;

class HtmlElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * testowanie metody text()
     */
    public function testText()
    {
        $head = new HtmlElement('head', 'foo');
        $this->assertEquals('foo', $head->text());
        $head->text('bar');
        $this->assertEquals('bar', $head->text());
        $head->destroy($head);
    }

    /**
     * sprawdzanie czy nazwy się zgadzają
     */
    public function testName()
    {
        $el = new HtmlElement('head');
        $this->assertEquals('head', $el->name());
        $el->destroy($el);
        $el = new HtmlElement('link');
        $this->assertEquals('link', $el->name());
    }

    public function helpTest1(HtmlElement $head)
    {
        $this->assertInstanceOf('vSymfo\Component\Document\Element\HtmlElement', $head);
        $this->assertEquals(3, $head->xpath('script')->length);
    }

    /**
     * niszczenie obiektów
     */
    public function testUnset()
    {
        $head = new HtmlElement('head');
        $script = new HtmlElement('script');
        $head->insert($script);
        $script->destroy($script);
        $this->assertEquals(0, $head->xpath('script')->length);
        $head->destroy($head);

        $h = new HtmlElement('head');
        $h->insert(new HtmlElement('script'));
        $h->insert(new HtmlElement('script'));
        $h->insert(new HtmlElement('script'));
        $this->helpTest1($h);
        $h->destroy($h);
    }

    /**
     * test klonowania
     */
    public function testClone()
    {
        $el = new HtmlElement('meta');
        $el2 = clone $el;
        $el3 = $el;
        $this->assertNotSame($el->getElement(), $el2->getElement());
        $this->assertSame($el->getElement(), $el3->getElement());
        $el->destroy($el);
        $el2->destroy($el2);
    }

    /**
     * wstawianie kodu html
     */
    public function testInsertHtml()
    {
        $el = new HtmlElement('head');
        $el->insertHtml('<span class="ma">ma </span>');
        $el->insertHtml('<span class="ala">Ala </span>', $el::CHILD_PREPEND);
        $el->insertHtml('<span class="kota">kota</span>', $el::CHILD_APPEND);
        $span = $el->xpath('span');
        $this->assertEquals('ala', $span->item(0)->getAttribute('class'));
        $this->assertEquals('ma', $span->item(1)->getAttribute('class'));
        $this->assertEquals('kota', $span->item(2)->getAttribute('class'));
        $el->destroy($el);
    }

    /**
     * test atrybutów
     */
    public function attrTest()
    {
        $el = new HtmlElement('link');
        $el->attr('test', 'ok');
        $this->assertEquals('ok', $el->attr('test'));
        $el->removeAttr('test');
        $this->assertEquals('', $el->attr('test'));
        $el->destroy($el);
    }

    /**
     * testowanie atrybutu class
     */
    public function classTest()
    {
        $el = new HtmlElement('link');
        $el->addClass('test1');
        $el->addClass('test');
        $el->removeClass('test');
        $this->assertEquals('test1', $el->attr('class'));
        $el->destroy($el);
    }

    /**
     * test wstawiania elementu do elementu
     */
    public function insertTest()
    {
        $script = new HtmlElement('script');
        $head = new HtmlElement('head');
        $script->insertTo($head);
        $this->assertEquals(1, $head->xpath('script')->length);
        $script = new HtmlElement('script');
        $head->insert($script);
        $this->assertEquals(2, $head->xpath('script')->length);
    }
}
