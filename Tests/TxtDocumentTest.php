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

use vSymfo\Component\Document\Format\TxtDocument;
use vSymfo\Component\Document\FileLoader\TranslationLoader;
use Symfony\Component\Config\FileLocator;

class TxtDocumentTest extends \PHPUnit_Framework_TestCase
{
    protected $doc;

    public function __construct()
    {
        $this->doc = new TxtDocument();
    }

    /**
     * testowanie renderowania
     */
    public function testRender()
    {
        $body = $this->doc->body('abc');
        $body->update(function ($text) {
            return $text . ' defg';
        });

        $this->assertEquals('abc defg', $this->doc->render());
        $this->doc->title('Title');
        $this->assertEquals('Title' . PHP_EOL . PHP_EOL . 'abc defg', $this->doc->render());
    }

    /**
     * testowanie tłumaczeń
     */
    public function testTranslations()
    {
        $this->doc->title('');
        $this->doc->addTranslation(array(
                'LOREM_IPSUM' => 'Lorem Ipsum',
                'FOO_BAR' => 'Foo Bar'
            ));

        $this->doc->body('<trans>LOREM_IPSUM</trans> <trans>FOO_BAR</trans> <trans>UNKNOWN</trans>');
        $this->assertEquals('Lorem Ipsum Foo Bar UNKNOWN', $this->doc->render());

        $locator = new FileLocator(__DIR__ . '/tmp/config');
        $loader = new TranslationLoader($locator, array(
            'document' => $this->doc,
            'trans_closure' => function ($text) {
                switch ($text) {
                    case 'TRANS_TEST1':
                        return 'Test Word 1';
                    case 'TRANS_TEST2':
                        return 'Test Word 2';
                }

                return $text;
            },
            'cache_dir' => __DIR__ . '/tmp/cache',
            'cache_refresh' => true
        ));
        $loader->load('translations.yml');

        $this->doc->body('<trans>TRANS_TEST1</trans> <trans>TRANS_TEST2</trans>');
        $this->assertEquals('Test Word 1 Test Word 2', $this->doc->render());
    }

    /**
     * testowanie nazwy
     */
    public function testName()
    {
        $this->assertEquals('', $this->doc->name());
        $this->doc->name('     my     name     ' . PHP_EOL . '   is   test   ');
        $this->assertEquals('my name is test', $this->doc->name());
        $this->doc->name('');
    }

    /**
     * testowanie tytułu
     */
    public function testTitle()
    {
        $this->assertEquals('NAME', $this->doc->name('NAME'));
        $this->assertEquals('TITLE', $this->doc->title('TITLE'));
        $this->assertEquals('NAME', $this->doc->title('TITLE', TxtDocument::TITLE_ONLY_NAME));
        $this->assertEquals('NAME - TITLE', $this->doc->title('TITLE', TxtDocument::TITLE_FIRST_NAME));
        $this->assertEquals('TITLE - NAME', $this->doc->title('TITLE', TxtDocument::TITLE_FIRST_TITLE));
        $this->assertEquals('NAME ** TITLE', $this->doc->title('TITLE', TxtDocument::TITLE_FIRST_NAME, '**'));
        $this->assertEquals('NAME ** TITLE', $this->doc->title());
    }

    /**
     * testowanie tytułu
     */
    public function testAuthor()
    {
        $this->assertEquals('John Doe', $this->doc->author('     John  ' . PHP_EOL . '  Doe     '));
        $this->assertEquals('John Doe', $this->doc->author());

        $this->assertEquals('', $this->doc->authorUrl('/bad/url/'));
        $this->assertEquals('', $this->doc->authorUrl('bad.pl'));
        $this->assertEquals('ftp://john.com', $this->doc->authorUrl('ftp://john.com'));
        $this->assertEquals('mailto://john@doe.com', $this->doc->authorUrl('mailto://john@doe.com'));
        $this->assertEquals('https://www.google.com', $this->doc->authorUrl('https://www.google.com'));
    }

    /**
     * testowanie daty
     */
    public function testDate()
    {
        $now = new \DateTime('1970-01-01 00:00:00');
        $this->assertEquals($now->format('Y-m-d'), $this->doc->createdDate()->format('Y-m-d'));
        $this->doc->createdDate('2011-10-31 12:40:20');
        $this->assertEquals('2011-10-31', $this->doc->createdDate()->format('Y-m-d'));

        $this->assertEquals($now->format('Y-m-d'), $this->doc->lastModified()->format('Y-m-d'));
        $this->doc->lastModified('2011-10-31 12:40:20');
        $this->assertEquals('2011-10-31', $this->doc->lastModified()->format('Y-m-d'));
    }

    /**
     * testowanie opisu
     */
    public function testDescription()
    {
        $this->assertEquals('lorem ipsum', $this->doc->description('     lorem    ' . PHP_EOL . '  ipsum '));
        $this->assertEquals('lorem ipsum', $this->doc->description());
    }

    /**
     * testowania słów kluczowych
     */
    public function testKeywords()
    {
        $this->assertEquals('test, ok, test', $this->doc->keywords('                test,         ok,          test                                  '));
        $this->assertEquals('test, ok, test', $this->doc->keywords());
    }
}
