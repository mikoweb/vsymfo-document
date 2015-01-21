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

use vSymfo\Component\Document\Format\PdfDocument;
use vSymfo\Component\Document\Element\HtmlElement;

class PdfDocumentTest extends \PHPUnit_Framework_TestCase
{
    public function testTest()
    {
        $doc = new PdfDocument();
        $doc->title('Sample Page');
        $doc->body(file_get_contents(__DIR__ . '/tmp/pdf.html'));
        $head = $doc->element('head');
        $bootstrap = new HtmlElement('link');
        $bootstrap->attr('rel', 'stylesheet');
        $bootstrap->attr('type', 'text/css');
        $bootstrap->attr('href', 'http://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css');
        $bootstrap->insertTo($head);
        $style = new HtmlElement('style');
        $style->text(file_get_contents(__DIR__ . '/tmp/pdf.css'));
        $style->attr('type', 'text/css');
        $style->insertTo($head);

        $now = new DateTime();
        //$doc->lastModified($now->format('Y-m-d H:i:s'));
        $doc->setOptions(array(
            'dummy_pdf_url' => 'empty.pdf',
            'display_url' => 'test.pdf',
            'download_url' => 'test.pdf',
            'remote_url' => 'http://www.foo.bar/test.pdf',
            "pluginDetect_PDFReader_url" => "/pdf/PluginDetect_PDFReader.js",
            "waiting_view_path" => __DIR__ . '/tmp/waiting-view.html',
            "queue_db_path" => __DIR__ . '/tmp/pdf-queue.db',
            "wkhtmltopdf_global" => array(
                "binary" => "wkhtmltopdf"
            )
        ));
        $doc->outputSelector(function() {
            return isset($_GET['do']) ? $_GET['do'] : null;
        });
        $doc->setFilename(__DIR__ . '/tmp/cache/test.pdf');
        $doc->toc(true, __DIR__ . '/tmp/toc.xsl');
        $doc->addCover('start', 'http://www.loremipsum.net');
        $doc->addCover('end', 'http://www.loremipsum.net');
        //var_dump($doc->render());
    }
}
