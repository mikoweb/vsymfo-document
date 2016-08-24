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

namespace vSymfo\Component\Document\Format;

use vSymfo\Component\Document\Element\HtmlElement;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use vSymfo\Component\Document\Utility\QueuePdfDb;
use mikehaertl\wkhtmlto\Pdf;
use Stringy\Stringy as S;

/**
 * PDF document. Generated from HTML data.
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
class PdfDocument extends HtmlDocument
{
    /**
     * @var array
     */
    private $options = null;

    /**
     * @var Pdf
     */
    private $wkhtmltopdf = null;

    /**
     * @var string
     */
    private $filename = null;

    /**
     * Array with paths (HTML files) to covers.
     *
     * @var array
     */
    private $covers = array();

    /**
     * @var bool
     */
    private $useToc = false;

    /**
     * @var \Closure
     */
    private $outputSelector = null;

    /**
     * Queue requests file creation.
     *
     * @var QueuePdfDb
     */
    private $queue = null;

    public function __construct()
    {
        parent::__construct();
        $this->wkhtmltopdf = new Pdf();
        $this->outputSelector = function() {
            return null;
        };
    }

    /**
     * {@inheritdoc }
     */
    public function formatName()
    {
        return "pdf";
    }

    /**
     * Settings of document and WkHtmlToPdf.
     *
     * @param array $options
     * @link https://github.com/mikehaertl/phpwkhtmltopdf
     * @link http://wkhtmltopdf.org/usage/wkhtmltopdf.txt
     */
    public function setOptions(array $options)
    {
        $wkhtmltopdf_global = array(
            'page-size' => 'a4',
            'margin-top'    => 20,
            'margin-bottom' => 20,
            'margin-left'   => 12,
            'margin-right'  => 12,
            'header-spacing' => 6,
            'footer-spacing' => 6,
        );

        $wkhtmltopdf_page = array(
            'header-line', 'footer-line',
            'header-left' => '[title]',
            'header-right' => '[section]',
            'footer-center' => '[page]/[topage]'
        );

        $wkhtmltopdf_cover = array(
            'no-header-line', 'no-footer-line',
            'header-left' => '',
            'header-right' => '',
            'footer-center' => ''
        );

        $wkhtmltopdf_toc = array();

        $resolver = new OptionsResolver();
        $resolver->setRequired(array('dummy_pdf_url', 'display_url', 'download_url', 'remote_url', 'pluginDetect_PDFReader_url', 'waiting_view_path', 'queue_db_path'));
        $resolver->setDefaults(array(
            'wkhtmltopdf_global' => array(),
            'wkhtmltopdf_page'   => array(),
            'wkhtmltopdf_cover'  => array(),
            'wkhtmltopdf_toc'    => array()
        ));

        $resolver->setAllowedTypes('wkhtmltopdf_global', 'array');
        $resolver->setAllowedTypes('wkhtmltopdf_page', 'array');
        $resolver->setAllowedTypes('wkhtmltopdf_cover', 'array');
        $resolver->setAllowedTypes('wkhtmltopdf_toc', 'array');
        $resolver->setAllowedTypes('dummy_pdf_url', 'string');
        $resolver->setAllowedTypes('display_url', 'string');
        $resolver->setAllowedTypes('download_url', 'string');
        $resolver->setAllowedTypes('remote_url', 'string');
        // http://www.pinlady.net/PluginDetect/PDFReader/
        $resolver->setAllowedTypes('pluginDetect_PDFReader_url', 'string');
        $resolver->setAllowedTypes('waiting_view_path', 'string');

        $resolver->setNormalizer('wkhtmltopdf_global', function (Options $options, $value) use ($wkhtmltopdf_global) {
            return array_merge($wkhtmltopdf_global, $value);
        });
        $resolver->setNormalizer('wkhtmltopdf_page', function (Options $options, $value) use ($wkhtmltopdf_page) {
            return array_merge($wkhtmltopdf_page, $value);
        });
        $resolver->setNormalizer('wkhtmltopdf_cover', function (Options $options, $value) use ($wkhtmltopdf_cover) {
            return array_merge($wkhtmltopdf_cover, $value);
        });
        $resolver->setNormalizer('wkhtmltopdf_toc', function (Options $options, $value) use ($wkhtmltopdf_toc) {
            return array_merge($wkhtmltopdf_toc, $value);
        });
        $resolver->setNormalizer('waiting_view_path', function (Options $options, $value) {
            if (!file_exists($value)) {
                throw new \UnexpectedValueException('Error during parse waiting_view_path: File not found.');
            }
    
            $info = pathinfo($value);
            if ($info['extension'] != 'html') {
                throw new \UnexpectedValueException('Error during parse waiting_view_path: Unexpected file extension - ' . $info['extension']);
            }
    
            return $value;
        });

        $this->options = $resolver->resolve($options);
        $this->wkhtmltopdf->setOptions($this->options['wkhtmltopdf_global']);

        // kolejka żądań utworzenia pliku
        $this->queue = QueuePdfDb::openFile($this->options['queue_db_path']);
    }

    /**
     * Set output filename for save.
     *
     * @param string $filename
     *
     * @throws \InvalidArgumentException
     */
    public function setFilename($filename)
    {
        if (!is_string($filename)) {
            throw new \InvalidArgumentException('filename is not string');
        }

        $this->filename = $filename;
    }

    /**
     * Get output filename for save.
     *
     * @return string
     */
    public function getFilename()
    {
        return (string)$this->filename;
    }

    /**
     * Transform getFilename() to html path.
     *
     * @return string
     */
    public function getFilenameToHtml()
    {
        $info = pathinfo($this->getFilename());
        return $info['dirname'] . '/' . $info['filename'] . '.html';
    }

    /**
     * Add new Cover.
     *
     * @param string $name
     * @param string $url
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    public function addCover($name, $url, array $options = null)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('name is not string');
        }

        if (!is_string($url)) {
            throw new \InvalidArgumentException('url is not string');
        }

        $this->covers[$name] = array(
            'url' => $url,
            'options' => is_null($options) ? $this->options['wkhtmltopdf_cover'] : $options
        );
    }

    /**
     * Insert Table of Contents.
     *
     * @param bool $use
     * @param string $xslStyleSheet
     *
     * @throws \InvalidArgumentException
     */
    public function toc($use, $xslStyleSheet = null)
    {
        if (!is_bool($use)) {
            throw new \InvalidArgumentException('use is not bool');
        }

        $this->useToc = $use;
        if (is_string($xslStyleSheet) && isset($this->options['wkhtmltopdf_toc'])) {
            $this->options['wkhtmltopdf_toc']['xsl-style-sheet'] = $xslStyleSheet;
        }
    }

    /**
     * Throw exception if not set required options.
     *
     * @throws \RuntimeException
     */
    private function optionsTest()
    {
        if (!is_array($this->options)) {
            throw new \RuntimeException('Field "options" is not set. First use setOptions() method.');
        }

        if (!is_string($this->filename)) {
            throw new \RuntimeException('Field "filename" is not set. First use setFilename() method.');
        }
    }

    /**
     * Force update PDF file if is expired.
     *
     * @param $duration czas trwania
     */
    public function updateIfExpired($duration)
    {
        if ((int)$duration > -1) {
            $now = new \DateTime();
            if (file_exists($this->getFilename()) && filemtime($this->getFilename()) + (int)$duration < $now->getTimestamp()) {
                $this->lastModified($now->format('Y-m-d H:i:s'));
            }
        }
    }

    /**
     * Download PDF file.
     */
    public function download()
    {
        if (!file_exists($this->filename)) {
            throw new \RuntimeException('file: ' . $this->filename . ' not exists');
        }

        $buffer = file_get_contents($this->filename);

        header('Content-Description: File Transfer');
        header('Content-Transfer-Encoding: binary');
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream', false);
        header('Content-Type: application/download', false);
        header('Content-Type: application/pdf', false);
        header('Content-Length: ' . strlen($buffer));
        header('Content-Disposition: attachment; filename=' . S::create($this->title())->slugify() . '.pdf');
        ob_clean();
        flush();
        echo $buffer;
    }

    /**
     * Display PDF in browser.
     */
    public function display()
    {
        if (!file_exists($this->filename)) {
            throw new \RuntimeException('file: ' . $this->filename . ' not exists');
        }

        $buffer = file_get_contents($this->filename);

        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($buffer));
        header('Content-disposition: inline; filename="'.S::create($this->title())->slugify() . '.pdf"');
        ob_clean();
        flush();
        echo $buffer;
    }

    /**
     * Domknięcie zadecyduje o tym w jaki sposób zostanie wysłany dokument do użytkownika.
     * Aktualnie obsługiwane są dwa parametry.
     * Jeśli domknięcie zwróci:
     *  "download" => nastąpi pobieranie dokumentu
     *  "display" => wyświetlenie pdf za pomocą natywnego readera
     *  inna wartość (np. null) => wyświetlenie strony i testowanie przeglądarki
     * 
     * @param \Closure $closure
     */
    public function outputSelector(\Closure $closure)
    {
        $this->outputSelector = $closure;
    }

    /**
     * Ten widok jest tworzony, gdy nie można utworzyć pliku PDF z poziomu skryptu PHP.
     * Jest to widok, który odświeża się w regularnych odstępach czasu, oczekując na wygenerowanie pliku PDF,
     * przez zewnętrzne oprogramowanie odpalane z Crontab.
     * Żądanie wygenerowania pliku jest zapisywane w bazie SQLite. Kiedy zewnętrzne oprogramowanie wegeneruje plik PDF,
     * żądanie powinno się usunąć z bazy danych.
     *
     * @return string
     */
    private function waitingView()
    {
        return file_get_contents($this->options['waiting_view_path']);
    }

    /**
     * {@inheritdoc }
     */
    public function render()
    {
        $this->optionsTest();
        $exists = file_exists($this->filename);
        $pdfDate = new \DateTime();
        $pdfDate->setTimestamp($exists ? filemtime($this->filename) : 0);
        // generowanie pliku jeśli nie ma lub jest przestarzały
        if (!$exists || ($this->lastModified() > $pdfDate)) {
            $html = parent::render();
            if (isset($this->covers['start'])) {
                $this->wkhtmltopdf->addCover($this->covers['start']['url'], $this->covers['start']['options']);
            }

            if ($this->useToc) {
                $this->wkhtmltopdf->addToc($this->options['wkhtmltopdf_toc']);
            }

            $this->wkhtmltopdf->addPage($html, $this->options['wkhtmltopdf_page']);

            if (isset($this->covers['end'])) {
                $this->wkhtmltopdf->addCover($this->covers['end']['url'], $this->covers['end']['options']);
            }

            if (!$this->wkhtmltopdf->saveAs($this->filename)) {
                //throw new \Exception('Could not create PDF: ' . $this->wkhtmltopdf->getError());
                file_put_contents($this->getFilenameToHtml(), $html);
                $this->queue->beginTransaction();
                if ($this->queue->select($this->getFilename()) == false) {
                    $this->queue->insert($this->getFilename(), array(
                        'date_added' => time(),
                        'block' => 0,
                        'html_file' => $this->getFilenameToHtml()
                    ));
                }
                $this->queue->endTransaction();

                return $this->waitingView();
            }
        }

        switch (call_user_func($this->outputSelector)) {
            case 'download':
                $this->download();
                return '';
            case 'display':
                $this->display();
                return '';
        }

        $viewer = new HtmlDocument();
        $viewer->name($this->name());
        $viewer->title($this->title(), HtmlDocument::TITLE_ONLY_TITLE);
        $viewer->author($this->author());
        $viewer->authorUrl($this->authorUrl());
        $viewer->keywords($this->keywords());
        $viewer->description($this->description());

        $pdfDetect = str_replace(array('{dummy_pdf}', '{url_download}', '{url_display}', '{url_remote}'), array($this->options['dummy_pdf_url'], $this->options['download_url'], $this->options['display_url'], $this->options['remote_url']), base64_decode('PHNjcmlwdCB0eXBlPSJ0ZXh0L2phdmFzY3JpcHQiPg0KKGZ1bmN0aW9uKCl7DQogICAgInVzZSBzdHJpY3QiOw0KDQogICAgdmFyIER1bW15UERGID0gJ3tkdW1teV9wZGZ9JywNCiAgICAgICAgZGV0ZWN0Tm9uQWRvYmVJRSA9IDEsDQogICAgICAgIHZpZXdlciA9ICc8aWZyYW1lIHNyYz0iaHR0cHM6Ly9kb2NzLmdvb2dsZS5jb20vdmlld2VyP2VtYmVkZGVkPXRydWUmYW1wO3VybD17dXJsX3JlbW90ZX0iIHdpZHRoPSI4MDAiIGhlaWdodD0iNjAwIiBmcmFtZWJvcmRlcj0iMCIgc3R5bGU9ImJvcmRlcjogbm9uZTsgd2lkdGg6IDEwMCUgIWltcG9ydGFudDsgaGVpZ2h0OiAxMDAlICFpbXBvcnRhbnQ7IHBvc2l0aW9uOmFic29sdXRlOyBsZWZ0OiAwcHg7IHRvcDogMHB4OyI+PC9pZnJhbWU+JzsNCgkJdmlld2VyICs9ICc8YSBocmVmPSJ7dXJsX2Rvd25sb2FkfSI+PGltZyBzcmM9ImRhdGE6aW1hZ2UvcG5nO2Jhc2U2NCxpVkJPUncwS0dnb0FBQUFOU1VoRVVnQUFBQmdBQUFBWUNBWUFBQURnZHozNEFBQUFqa2xFUVZSNG5PMlZYUXFBUUFpRTNlaUtkZitMOVBOUWtwRG1xR3dRTk5EYk9GL3ExaEoxVmdNOFc2Vit3TjhscHgvZ1NsdlMwMUxEZVZvSGN5SjRqZFpOZEhTQ1BNdnBEd3VCcE1NUlNDbWNReXlJREpmZVVMZ0YwY0poaURVT0NiSENYWWkzVUlhZ1hpSzZQZ3gwaGkzb0RSVmtoRndIZFVMWERyci9UVWRKSytUd0ZHNkg1cFg3NE5zNzJBRll3VnJBemNhS1BnQUFBQUJKUlU1RXJrSmdnZz09IiBhbHQ9IkRvd25sb2FkIiB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIGJvcmRlcj0iMCIgc3R5bGU9InBvc2l0aW9uOmFic29sdXRlOyBsZWZ0OiAxNXB4OyB0b3A6IDVweDsiIC8+PC9hPic7DQogICAgUGx1Z2luRGV0ZWN0Lm9uRGV0ZWN0aW9uRG9uZSgnUERGUmVhZGVyJywgZnVuY3Rpb24gKCQkKSB7DQogICAgICAgIHZhciBzdGF0dXMgPSAkJC5pc01pblZlcnNpb24oJ1BERlJlYWRlcicsIDApOw0KICAgICAgICBpZiAoc3RhdHVzID49IC0wLjE1KSB7DQogICAgICAgICAgICBkb2N1bWVudC5sb2NhdGlvbiA9ICd7dXJsX2Rpc3BsYXl9JzsNCiAgICAgICAgfSBlbHNlIHsNCgkJCWRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCd2aWV3ZXInKS5pbm5lckhUTUwgPSB2aWV3ZXI7DQoJCX0NCiAgICB9LCBEdW1teVBERiwgZGV0ZWN0Tm9uQWRvYmVJRSk7DQp9KCkpOw0KPC9zY3JpcHQ+CQ=='));
        $viewer->body('<body><div id="viewer"><p style="text-align: center;"><a href="' . $this->options['download_url'] . '" title="Download PDF"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAMAAAD04JH5AAACZFBMVEUAAABEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREBEREAoGxsjHyAAAAAjICAmIyM0NDJEREBEREBEREAkHiEmIyMrKCpEREBEREBEREAiHiAnIyQkICIjHyAjHyBAAEAiHyAkHx8nIyMkHx8kICEiHx8jHyAvLywgICBAQDwhISEkICBAQD0kHSEkICAkHiAiHiAzMzMjHyA7OzkjHh4vLCsjHyErKCklICEqJSYmIyQnJCQzMzElJSQtKikjHx8qJiYjHx8oJSQjHx9AQD0nIyQjHyArKysuKy0hISEkHyEiHiAjHyAjHiAjHyAjHyAmHBwiHx8jHyEjHyAkICEkJCQjHyAnIyQ+PjslICAvLSsiICArKCcjICAoJSQjHiAkICEAAAAmIiMrKyk/Pzs/PzwkICEnIyMnIyQjHyAqJydCQj4kICAjICAjHx8jHx8nJycaGhokHx8kHiAmGiYiHyAjHyAjIyMkGyQjHyAjHyAiHCIhISEkHyAjHx8kHx8jHx8jICAkICErKSgjICAjHyArKCgkHx8qJyY3NzQjHiAjHiAjICAcHBwgICAjHiAiIiIjHx8nHR0jICAjHyAiIiIiHCIiHyAjHyAkHx8lICAjHyAjHx8mIyMlISEjHyAnIyQtKSokHiEnIyQrKSgjHiEjHyAlISEkICAiHyEjHx8jHyAkHyAkJCQjHyEjHyEjIyMkJCQjICAjHyAjHyAjHyAjICAmIiMmIyMkICAmIyMnIyMkICEnIyMnIySJqIzSAAAAwnRSTlMAAwMBBQYBCAkCCwwCDg8DEBIDExUDFhgT/gL+/ikcGQRN/l0fGwSG/oe/vgT0Mf5q/qPcBhgdF08gToiHwQX2CTNJbH6nsOLiMh1mUZiKysMk+PcGSzZwb6eo4OEbU4zFxwf4+SI3TXCAqbLi5QH+BRga/f39/ZMcgaWiuw0Ks8kUyNYdHNXlLSftOjlJSPlrWPmFcpUFoJ+xCQiwD9Ma0t8mJd7oMjDn80k+7/pqVPx6ZvyKgJWTrqUOvMwWFcrU0Z5netoAAAAJcEhZcwAAAEgAAABIAEbJaz4AAAWzSURBVHja7Zv3f9pGGIdlDAaDwWBkiGUXaAvUTexM1y1N7XTvvdvUSdt0jwwnpjNN90j3Hmm69957KjHzn6pOA+4Oueg9iZN/8P0SPu9Hx/MNHNKjVz5BWGyjy+PxdLlQ00e31+fzebu514zR4w8EAv4e7jVj9AZDoVCwl3vNGH3hSCQS7uNeM0Z/NBaLRfu514wxEBdFMT7AvWaMwUQymUwMLhuSm+PAwfn5+YMHZHmBmqTPHW5zHFYbOSQxaP7/T6RSqXRGOBQ7vlSuVCrlEvEeRE0PkDmszXF47fCM+fcfT6ayubwgHNE8vlqr1+u1KvEeZE0LkM+NtjkOrx1JcLv181FfVEymEF9YDuBrAfK57Kh1fnUFzu/xetR/e8MxMZlG/LFxAF8NoPCzo9b58vhYk9/r96kBeoKRmJhQv5uVED4KgPjZVdb5sryywe8LBtQA3f5QJBbX1uZqCF8JoPJzawB8eXVj3YVDAR9aA95AKBLVf5trIXxZ0vj5IQBfXmv87qKRUMCrvOjyBUJh49y0DsKXJzS+MATgy+v08048Fgn50TXR4wsEjXPzUSB+dVLj4wEszD1aPXckxFgkqF4TPT5/49p0DIhfK2h8LICVucei3046KcbCGtfjbV6b14P49YLGbwawNPc49NtJJcWo/rl3YW4yBeLXCxq/EcDa3Cn020kl4ybXxOkNIH59UuMbASzO3XC8snZTCbNr4gkwfm1CwANYnnuisnbTptfEk2D8qoQHsD73ZGXtml8TT4HxZTwAIPup+tptGfnTYHw8AOSzO30h/hlAPhYA9t2dacrP5M4C8psBgGvnbDP+YDp7DpDfCLAGuHbONeErTpg9D8g3AuRXAb+781v5yAkvuBDIZ3JCVLvoYoNLOOElUD6TE6qvL9X5pBNeBuUzOaE6Ltf4lBNeAeWzOSEaV2qfO+WEG6F8NidE4yp13VFOOAPmszmhOmZMnHATmM/mhOrYZOKEm8F8RidEY7OJE14N5jM6IRrXtDrhtVvAfEYnRLXrrm9xwhvgfEYnVGs3tjjhTXA+oxOqtZtbnPAWOJ/VCVHt1hYnvA3OZ3VCVLu95Xq4VT+v077Go2YEcJWvBHCXL2zNuMsXhstQJ7ZXkyj+wLYKVz4doD8+UuHK1wNgTjhS4crXAuBOOFLmylcDEE64rcSVjwKQTjjMl68EoJwQ7nX2ahLthHCvs1fbTjsh3Ots1Uo7aCeEe52tWnmWdkK419mqVWZpJ2TwOju1yizthAxeZ6dW2UE7IYPX2amVtwtmAbjxayXJLADLPYExdjL2GPEATPcEhuvsYuwxYgHY7gkM15pj7DE2A5TY7gkM15tj7DEyO6FE8rNzvJ1QIvnZXbydUCL5uZ0wvn0nlEh+vgjj23dCieQLRRjfvhNKJB8PwMcJJZKPBeDkhBLJbwbg5YQSyW8E4OaEEnWvW4Tx7TuhRN1rF0F8B5xQou71iyC+A044QfUaiiC+A044SfU6irydsED1Woq8nbBA9XqKnXTCO+5k6j/d5ZwT3j0N50/f46QT3rsbyr9vj7NOeP8DMP6yB512wocehvAfedR5J3zscev8JzZ2wgn3PmmV/9Tezjjh+NPW+M+Md8oJn33OCv/5FzrnhKMvtue/ZO3vKVid8OVX2vDXd9oJXx37P/7Ya513wtffWJi/700eTji1fyH+/uV8nPCtGXP+2+/wcsJ33zPjv/8BPyf88KNW/sef8HTCTz+j+Z9/wdcJv/yK5H+9hbcTfvMtzv/ue+59wnrth+bkH6E9Ngf6hErtJ/1NpJ8Zeqe2+4So9ss+NHP6V5bere0+ofp6xW+CsPt3pt6x7T6hNv7486+/2XrXTj07/udfxt750rPjpWfHS8+Ol54dL+Jnx2332DlTmzXdYyI09h12vhZfnPsu3d536va+W7f3Hbu979rtfecu7bv/D4Py4UgrkfZnAAAAJXRFWHRkYXRlOmNyZWF0ZQAyMDEzLTEwLTMxVDExOjQxOjI2LTA1OjAwBGQC+wAAACV0RVh0ZGF0ZTptb2RpZnkAMjAxMy0xMC0zMVQxMTo0MToyNi0wNTowMHU5ukcAAAAASUVORK5CYII=" width="128" height="128" alt="click here to download" /></a></p></div>
        <noscript><meta http-equiv="Refresh" content="0; url=' . $this->options['download_url'] . '" /></noscript>' . $pdfDetect . '</body>');
        $head = $viewer->element('head');
        $script = new HtmlElement('script');
        $script->attr('type', 'text/javascript');
        $script->attr('src', $this->options['pluginDetect_PDFReader_url']);
        $script->insertTo($head);
        $style = new HtmlElement('style');
        $style->text('body {margin: 0; padding: 0; overflow: hidden;}');
        $style->attr('type', 'text/css');
        $style->insertTo($head);
        $link = new HtmlElement('link');
        $link->attr('href', $this->options['download_url'])
            ->attr('rel', 'alternate')
            ->attr('type', 'application/pdf')
            ->attr('title', $this->title())
            ->attr('media', 'print')
            ->insertTo($head)
        ;

        return $viewer->render();
    }

    /**
     * @return WkHtmlToPdf
     */
    public function getWkhtmltopdf()
    {
        return $this->wkhtmltopdf;
    }
}
