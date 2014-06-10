<?php

/*
 * This file is part of the Joomla Rapid Framework
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
use WkHtmlToPdf;
use Stringy\Stringy as S;

/**
 * Dokument pdf
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
class PdfDocument extends HtmlDocument
{
    /**
     * opcje
     * @var array
     */
    private $options = null;

    /**
     * @var WkHtmlToPdf
     */
    private $wkhtmltopdf = null;

    /**
     * @var string
     */
    private $filename = null;

    /**
     * tablica ze ścieżkami (pliki html) do okładek
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

    public function __construct()
    {
        parent::__construct();
        $this->wkhtmltopdf = new WkHtmlToPdf();
        $this->outputSelector = function() {
            return null;
        };
    }

    /**
     * ustawienia dokumentu i WkHtmlToPdf
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
        $resolver->setRequired(array('dummy_pdf_url', 'display_url', 'download_url', 'remote_url'));
        $resolver->setDefaults(array(
            'wkhtmltopdf_global' => array(),
            'wkhtmltopdf_page'   => array(),
            'wkhtmltopdf_cover'  => array(),
            'wkhtmltopdf_toc'    => array()
        ));

        $resolver->setAllowedTypes(array(
            'wkhtmltopdf_global' => 'array',
            'wkhtmltopdf_page'   => 'array',
            'wkhtmltopdf_cover'  => 'array',
            'wkhtmltopdf_toc'    => 'array',
            'dummy_pdf_url'      => 'string',
            'display_url'        => 'string',
            'download_url'       => 'string',
            'remote_url'         => 'string'
        ));

        $resolver->setNormalizers(array(
                'wkhtmltopdf_global' => function (Options $options, $value) use ($wkhtmltopdf_global) {
                        return array_merge($wkhtmltopdf_global, $value);
                    },
                'wkhtmltopdf_page' => function (Options $options, $value) use ($wkhtmltopdf_page) {
                        return array_merge($wkhtmltopdf_page, $value);
                    },
                'wkhtmltopdf_cover' => function (Options $options, $value) use ($wkhtmltopdf_cover) {
                        return array_merge($wkhtmltopdf_cover, $value);
                    },
                'wkhtmltopdf_toc' => function (Options $options, $value) use ($wkhtmltopdf_toc) {
                        return array_merge($wkhtmltopdf_toc, $value);
                    },
            ));

        $this->options = $resolver->resolve($options);
        $this->wkhtmltopdf->setOptions($this->options['wkhtmltopdf_global']);
        $this->wkhtmltopdf->setPageOptions($this->options['wkhtmltopdf_page']);
    }

    /**
     * ustaw docelową ścieżkę, gdzie zostanie zapisany plik
     * @param string $filename
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
     * dodaj nową okładkę
     * @param string $name
     * @param string $url
     * @param array $options
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
     * Wstaw spis treści
     * @param bool $use
     * @param string $xslStyleSheet
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
     * Rzuć wyjątek jeżeli nie ustawiono wymanych pól
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
     * pobieranie dokumentu
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
     * wyświetlanie dokumentu
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
     * Domknięcie zadyceduje o tym w jaki sposób zostanie wysłany dokument do użytkownika.
     * Aktualnie obsługiwane są dwa parametry.
     * Jeśli domknięcie zwróci:
     *  "download" => nastąpi pobieranie dokumentu
     *  "display" => wyświetlenie pdf za pomocą natywnego readera
     *  inna wartość (np. null) => wyświetlenie strony i testowanie przeglądarki
     * @param \Closure $closure
     */
    public function outputSelector(\Closure $closure)
    {
        $this->outputSelector = $closure;
    }

    /**
     * @return string
     * @throws \Exception
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

            $this->wkhtmltopdf->addPage($html);

            if (isset($this->covers['end'])) {
                $this->wkhtmltopdf->addCover($this->covers['end']['url'], $this->covers['end']['options']);
            }

            if (!$this->wkhtmltopdf->saveAs($this->filename)) {
                throw new \Exception('Could not create PDF: ' . $this->wkhtmltopdf->getError());
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

        // http://www.pinlady.net/PluginDetect/PDFReader/
        $pluginDetect = base64_decode('dmFyIFBsdWdpbkRldGVjdD17dmVyc2lvbjoiMC44LjciLG5hbWU6IlBsdWdpbkRldGVjdCIsb3BlblRhZzoiPCIsaGFzT3duUHJvcGVydHk6KHt9KS5jb25zdHJ1Y3Rvci5wcm90b3R5cGUuaGFzT3duUHJvcGVydHksaGFzT3duOmZ1bmN0aW9uKGMsZCl7dmFyIGIsYTt0cnl7YT10aGlzLmhhc093blByb3BlcnR5LmNhbGwoYyxkKX1jYXRjaChiKXt9cmV0dXJuICEhYX0scmd4OntzdHI6L3N0cmluZy9pLG51bTovbnVtYmVyL2ksZnVuOi9mdW5jdGlvbi9pLGFycjovYXJyYXkvaSxhbnk6L0Jvb2xlYW58U3RyaW5nfE51bWJlcnxGdW5jdGlvbnxBcnJheXxEYXRlfFJlZ0V4cHxFcnJvci9pfSx0b1N0cmluZzooe30pLmNvbnN0cnVjdG9yLnByb3RvdHlwZS50b1N0cmluZyxpc1BsYWluT2JqZWN0OmZ1bmN0aW9uKGMpe3ZhciBhPXRoaXMsYjtpZighY3x8YS5yZ3guYW55LnRlc3QoYS50b1N0cmluZy5jYWxsKGMpKXx8Yy53aW5kb3c9PWN8fGEucmd4Lm51bS50ZXN0KGEudG9TdHJpbmcuY2FsbChjLm5vZGVUeXBlKSkpe3JldHVybiAwfXRyeXtpZighYS5oYXNPd24oYywiY29uc3RydWN0b3IiKSYmIWEuaGFzT3duKGMuY29uc3RydWN0b3IucHJvdG90eXBlLCJpc1Byb3RvdHlwZU9mIikpe3JldHVybiAwfX1jYXRjaChiKXtyZXR1cm4gMH1yZXR1cm4gMX0saXNEZWZpbmVkOmZ1bmN0aW9uKGIpe3JldHVybiB0eXBlb2YgYiE9InVuZGVmaW5lZCJ9LGlzQXJyYXk6ZnVuY3Rpb24oYil7cmV0dXJuIHRoaXMucmd4LmFyci50ZXN0KHRoaXMudG9TdHJpbmcuY2FsbChiKSl9LGlzU3RyaW5nOmZ1bmN0aW9uKGIpe3JldHVybiB0aGlzLnJneC5zdHIudGVzdCh0aGlzLnRvU3RyaW5nLmNhbGwoYikpfSxpc051bTpmdW5jdGlvbihiKXtyZXR1cm4gdGhpcy5yZ3gubnVtLnRlc3QodGhpcy50b1N0cmluZy5jYWxsKGIpKX0saXNTdHJOdW06ZnVuY3Rpb24oYil7cmV0dXJuIHRoaXMuaXNTdHJpbmcoYikmJigvXGQvKS50ZXN0KGIpfSxpc0Z1bmM6ZnVuY3Rpb24oYil7cmV0dXJuIHRoaXMucmd4LmZ1bi50ZXN0KHRoaXMudG9TdHJpbmcuY2FsbChiKSl9LGdldE51bVJlZ3g6L1tcZF1bXGRcLlxfLFwtXSovLHNwbGl0TnVtUmVneDovW1wuXF8sXC1dL2csZ2V0TnVtOmZ1bmN0aW9uKGIsYyl7dmFyIGQ9dGhpcyxhPWQuaXNTdHJOdW0oYik/KGQuaXNEZWZpbmVkKGMpP25ldyBSZWdFeHAoYyk6ZC5nZXROdW1SZWd4KS5leGVjKGIpOm51bGw7cmV0dXJuIGE/YVswXTpudWxsfSxjb21wYXJlTnVtczpmdW5jdGlvbihoLGYsZCl7dmFyIGU9dGhpcyxjLGIsYSxnPXBhcnNlSW50O2lmKGUuaXNTdHJOdW0oaCkmJmUuaXNTdHJOdW0oZikpe2lmKGUuaXNEZWZpbmVkKGQpJiZkLmNvbXBhcmVOdW1zKXtyZXR1cm4gZC5jb21wYXJlTnVtcyhoLGYpfWM9aC5zcGxpdChlLnNwbGl0TnVtUmVneCk7Yj1mLnNwbGl0KGUuc3BsaXROdW1SZWd4KTtmb3IoYT0wO2E8TWF0aC5taW4oYy5sZW5ndGgsYi5sZW5ndGgpO2ErKyl7aWYoZyhjW2FdLDEwKT5nKGJbYV0sMTApKXtyZXR1cm4gMX1pZihnKGNbYV0sMTApPGcoYlthXSwxMCkpe3JldHVybiAtMX19fXJldHVybiAwfSxmb3JtYXROdW06ZnVuY3Rpb24oYixjKXt2YXIgZD10aGlzLGEsZTtpZighZC5pc1N0ck51bShiKSl7cmV0dXJuIG51bGx9aWYoIWQuaXNOdW0oYykpe2M9NH1jLS07ZT1iLnJlcGxhY2UoL1xzL2csIiIpLnNwbGl0KGQuc3BsaXROdW1SZWd4KS5jb25jYXQoWyIwIiwiMCIsIjAiLCIwIl0pO2ZvcihhPTA7YTw0O2ErKyl7aWYoL14oMCspKC4rKSQvLnRlc3QoZVthXSkpe2VbYV09UmVnRXhwLiQyfWlmKGE+Y3x8ISgvXGQvKS50ZXN0KGVbYV0pKXtlW2FdPSIwIn19cmV0dXJuIGUuc2xpY2UoMCw0KS5qb2luKCIsIil9LGdldFBST1A6ZnVuY3Rpb24oZCxiLGEpe3ZhciBjO3RyeXtpZihkKXthPWRbYl19fWNhdGNoKGMpe31yZXR1cm4gYX0sZmluZE5hdlBsdWdpbjpmdW5jdGlvbihoKXtpZihoLmRidWcpe3JldHVybiBoLmRidWd9aWYod2luZG93Lm5hdmlnYXRvcil7dmFyIGQ9dGhpcyxuPXtGaW5kOmQuaXNTdHJpbmcoaC5maW5kKT9uZXcgUmVnRXhwKGguZmluZCwiaSIpOmguZmluZCxGaW5kMjpkLmlzU3RyaW5nKGguZmluZDIpP25ldyBSZWdFeHAoaC5maW5kMiwiaSIpOmguZmluZDIsQXZvaWQ6aC5hdm9pZD8oZC5pc1N0cmluZyhoLmF2b2lkKT9uZXcgUmVnRXhwKGguYXZvaWQsImkiKTpoLmF2b2lkKTowLE51bTpoLm51bT8vXGQvOjB9LGYsYyxnLGosbSxsLGIsYT1uYXZpZ2F0b3IubWltZVR5cGVzLGs9bmF2aWdhdG9yLnBsdWdpbnMsbz1udWxsO2lmKGgubWltZXMmJmEpe209ZC5pc0FycmF5KGgubWltZXMpP1tdLmNvbmNhdChoLm1pbWVzKTooZC5pc1N0cmluZyhoLm1pbWVzKT9baC5taW1lc106W10pO2ZvcihmPTA7ZjxtLmxlbmd0aDtmKyspe2M9MDt0cnl7aWYoZC5pc1N0cmluZyhtW2ZdKSYmL1teXHNdLy50ZXN0KG1bZl0pKXtjPWFbbVtmXV0uZW5hYmxlZFBsdWdpbn19Y2F0Y2goail7fWlmKGMpe2c9ZC5maW5kTmF2UGx1Z2luXyhjLG4pO2lmKGcub2JqKXtvPWcub2JqfTtpZihvJiYhZC5kYnVnKXtyZXR1cm4gb319fX1pZihoLnBsdWdpbnMmJmspe2w9ZC5pc0FycmF5KGgucGx1Z2lucyk/W10uY29uY2F0KGgucGx1Z2lucyk6KGQuaXNTdHJpbmcoaC5wbHVnaW5zKT9baC5wbHVnaW5zXTpbXSk7Zm9yKGY9MDtmPGwubGVuZ3RoO2YrKyl7Yz0wO3RyeXtpZihsW2ZdJiZkLmlzU3RyaW5nKGxbZl0pKXtjPWtbbFtmXV19fWNhdGNoKGope31pZihjKXtnPWQuZmluZE5hdlBsdWdpbl8oYyxuKTtpZihnLm9iail7bz1nLm9ian07aWYobyYmIWQuZGJ1Zyl7cmV0dXJuIG99fX1iPWsubGVuZ3RoO2lmKGQuaXNOdW0oYikpe2ZvcihmPTA7ZjxiO2YrKyl7Yz0wO3RyeXtjPWtbZl19Y2F0Y2goail7fWlmKGMpe2c9ZC5maW5kTmF2UGx1Z2luXyhjLG4pO2lmKGcub2JqKXtvPWcub2JqfTtpZihvJiYhZC5kYnVnKXtyZXR1cm4gb319fX19fXJldHVybiBvfSxmaW5kTmF2UGx1Z2luXzpmdW5jdGlvbihmLGQpe3ZhciBlPXRoaXMsYz1mLmRlc2NyaXB0aW9ufHwiIixiPWYubmFtZXx8IiIsYT17fTtpZigoZC5GaW5kLnRlc3QoYykmJighZC5GaW5kMnx8ZC5GaW5kMi50ZXN0KGIpKSYmKCFkLk51bXx8ZC5OdW0udGVzdChSZWdFeHAubGVmdENvbnRleHQrUmVnRXhwLnJpZ2h0Q29udGV4dCkpKXx8KGQuRmluZC50ZXN0KGIpJiYoIWQuRmluZDJ8fGQuRmluZDIudGVzdChjKSkmJighZC5OdW18fGQuTnVtLnRlc3QoUmVnRXhwLmxlZnRDb250ZXh0K1JlZ0V4cC5yaWdodENvbnRleHQpKSkpe2lmKCFkLkF2b2lkfHwhKGQuQXZvaWQudGVzdChjKXx8ZC5Bdm9pZC50ZXN0KGIpKSl7YS5vYmo9Zn19cmV0dXJuIGF9LGdldFZlcnNpb25EZWxpbWl0ZXI6IiwiLGZpbmRQbHVnaW46ZnVuY3Rpb24oZCl7dmFyIGM9dGhpcyxiLGQsYT17c3RhdHVzOi0zLHBsdWdpbjowfTtpZighYy5pc1N0cmluZyhkKSl7cmV0dXJuIGF9aWYoZC5sZW5ndGg9PTEpe2MuZ2V0VmVyc2lvbkRlbGltaXRlcj1kO3JldHVybiBhfWQ9ZC50b0xvd2VyQ2FzZSgpLnJlcGxhY2UoL1xzL2csIiIpO2I9Yy5QbHVnaW5zW2RdO2lmKCFifHwhYi5nZXRWZXJzaW9uKXtyZXR1cm4gYX1hLnBsdWdpbj1iO2Euc3RhdHVzPTE7cmV0dXJuIGF9LEFYTzooZnVuY3Rpb24oKXt2YXIgYixhO3RyeXtiPW5ldyB3aW5kb3cuQWN0aXZlWE9iamVjdCgpfWNhdGNoKGEpe31yZXR1cm4gYj9udWxsOndpbmRvdy5BY3RpdmVYT2JqZWN0fSkoKSxnZXRBWE86ZnVuY3Rpb24oYSl7dmFyIGQ9bnVsbCxjLGI9dGhpczt0cnl7ZD1uZXcgYi5BWE8oYSl9Y2F0Y2goYyl7fTtpZihkKXtiLmJyb3dzZXIuQWN0aXZlWEVuYWJsZWQ9ITB9cmV0dXJuIGR9LGJyb3dzZXI6e30sSU5JVDpmdW5jdGlvbigpe3RoaXMuaW5pdC5saWJyYXJ5KHRoaXMpfSxpbml0OnskOjEsaGFzUnVuOjAsb2JqUHJvcGVydGllczpmdW5jdGlvbihkLGUsYyl7dmFyIGEsYj17fTtpZihlJiZjKXtpZihlW2NbMF1dPT09MSYmZC5oYXNPd24oZSxjWzBdKSYmZC5pc1BsYWluT2JqZWN0KGUpKXtmb3IoYT0wO2E8Yy5sZW5ndGg7YT1hKzIpe2VbY1thXV09Y1thKzFdO2JbY1thXV09MX19Zm9yKGEgaW4gZSl7aWYoZVthXSYmZVthXVtjWzBdXT09PTEmJmQuaGFzT3duKGUsYSkmJiFkLmhhc093bihiLGEpKXt0aGlzLm9ialByb3BlcnRpZXMoZCxlW2FdLGMpfX19fSxwbHVnaW46ZnVuY3Rpb24oYSxjKXt2YXIgZD10aGlzLGI9ZC4kO2lmKGIuaXNQbGFpbk9iamVjdChhKSYmYi5pc0Z1bmMoYS5nZXRWZXJzaW9uKSl7aWYoIWIuaXNEZWZpbmVkKGEuZ2V0VmVyc2lvbkRvbmUpKXthLmluc3RhbGxlZD1udWxsO2EudmVyc2lvbj1udWxsO2EudmVyc2lvbjA9bnVsbDthLmdldFZlcnNpb25Eb25lPW51bGw7YS5wbHVnaW5OYW1lPWN9ZC5vYmpQcm9wZXJ0aWVzKGIsYSxbIiQiLGIsIiQkIixhXSl9fSxkZXRlY3RJRTpmdW5jdGlvbigpe3ZhciBpbml0PXRoaXMsJD1pbml0LiQsYnJvd3Nlcj0kLmJyb3dzZXIsZG9jPWRvY3VtZW50LGUseCx0bXAsdXNlckFnZW50PXdpbmRvdy5uYXZpZ2F0b3I/bmF2aWdhdG9yLnVzZXJBZ2VudHx8IiI6IiIscHJvZ2lkLHByb2dpZDEscHJvZ2lkMjticm93c2VyLkFjdGl2ZVhGaWx0ZXJpbmdFbmFibGVkPSExO2Jyb3dzZXIuQWN0aXZlWEVuYWJsZWQ9ITE7dHJ5e2Jyb3dzZXIuQWN0aXZlWEZpbHRlcmluZ0VuYWJsZWQ9ISF3aW5kb3cuZXh0ZXJuYWwubXNBY3RpdmVYRmlsdGVyaW5nRW5hYmxlZCgpfWNhdGNoKGUpe31wcm9naWQxPVsiTXN4bWwyLlhNTEhUVFAiLCJNc3htbDIuRE9NRG9jdW1lbnQiLCJNaWNyb3NvZnQuWE1MRE9NIiwiVERDQ3RsLlREQ0N0bCIsIlNoZWxsLlVJSGVscGVyIiwiSHRtbERsZ1NhZmVIZWxwZXIuSHRtbERsZ1NhZmVIZWxwZXIiLCJTY3JpcHRpbmcuRGljdGlvbmFyeSJdO3Byb2dpZDI9WyJXTVBsYXllci5PQ1giLCJTaG9ja3dhdmVGbGFzaC5TaG9ja3dhdmVGbGFzaCIsIkFnQ29udHJvbC5BZ0NvbnRyb2wiXTtwcm9naWQ9cHJvZ2lkMS5jb25jYXQocHJvZ2lkMik7Zm9yKHg9MDt4PHByb2dpZC5sZW5ndGg7eCsrKXtpZigkLmdldEFYTyhwcm9naWRbeF0pJiYhJC5kYnVnKXticmVha319aWYoYnJvd3Nlci5BY3RpdmVYRW5hYmxlZCYmYnJvd3Nlci5BY3RpdmVYRmlsdGVyaW5nRW5hYmxlZCl7Zm9yKHg9MDt4PHByb2dpZDIubGVuZ3RoO3grKyl7aWYoJC5nZXRBWE8ocHJvZ2lkMlt4XSkpe2Jyb3dzZXIuQWN0aXZlWEZpbHRlcmluZ0VuYWJsZWQ9ITE7YnJlYWt9fX07dG1wPWRvYy5kb2N1bWVudE1vZGU7dHJ5e2RvYy5kb2N1bWVudE1vZGU9IiJ9Y2F0Y2goZSl7fWJyb3dzZXIuaXNJRT1icm93c2VyLkFjdGl2ZVhFbmFibGVkfHwkLmlzTnVtKGRvYy5kb2N1bWVudE1vZGUpfHxldmFsKCIvKkBjY19vbiFAKi8hMSIpO3RyeXtkb2MuZG9jdW1lbnRNb2RlPXRtcH1jYXRjaChlKXt9O2Jyb3dzZXIudmVySUU9bnVsbDtpZihicm93c2VyLmlzSUUpe2Jyb3dzZXIudmVySUU9KCQuaXNOdW0oZG9jLmRvY3VtZW50TW9kZSkmJmRvYy5kb2N1bWVudE1vZGU+PTc/ZG9jLmRvY3VtZW50TW9kZTowKXx8KCgvXig/Oi4qP1teYS16QS1aXSk/Pyg/Ok1TSUV8cnZccypcOilccyooXGQrXC4/XGQqKS9pKS50ZXN0KHVzZXJBZ2VudCk/cGFyc2VGbG9hdChSZWdFeHAuJDEsMTApOjcpfX0sZGV0ZWN0Tm9uSUU6ZnVuY3Rpb24oKXt2YXIgZj10aGlzLGQ9dGhpcy4kLGE9ZC5icm93c2VyLGU9d2luZG93Lm5hdmlnYXRvcj9uYXZpZ2F0b3I6e30sYz1hLmlzSUU/IiI6ZS51c2VyQWdlbnR8fCIiLGc9ZS52ZW5kb3J8fCIiLGI9ZS5wcm9kdWN0fHwiIjthLmlzR2Vja289KC9HZWNrby9pKS50ZXN0KGIpJiYoL0dlY2tvXHMqXC9ccypcZC9pKS50ZXN0KGMpO2EudmVyR2Vja289YS5pc0dlY2tvP2QuZm9ybWF0TnVtKCgvcnZccypcOlxzKihbXC5cLFxkXSspL2kpLnRlc3QoYyk/UmVnRXhwLiQxOiIwLjkiKTpudWxsO2EuaXNDaHJvbWU9KC8oQ2hyb21lfENyaU9TKVxzKlwvXHMqKFxkW1xkXC5dKikvaSkudGVzdChjKTthLnZlckNocm9tZT1hLmlzQ2hyb21lP2QuZm9ybWF0TnVtKFJlZ0V4cC4kMik6bnVsbDthLmlzU2FmYXJpPSFhLmlzQ2hyb21lJiYoKC9BcHBsZS9pKS50ZXN0KGcpfHwhZykmJigvU2FmYXJpXHMqXC9ccyooXGRbXGRcLl0qKS9pKS50ZXN0KGMpO2EudmVyU2FmYXJpPWEuaXNTYWZhcmkmJigvVmVyc2lvblxzKlwvXHMqKFxkW1xkXC5dKikvaSkudGVzdChjKT9kLmZvcm1hdE51bShSZWdFeHAuJDEpOm51bGw7YS5pc09wZXJhPSgvT3BlcmFccypbXC9dP1xzKihcZCtcLj9cZCopL2kpLnRlc3QoYyk7YS52ZXJPcGVyYT1hLmlzT3BlcmEmJigoL1ZlcnNpb25ccypcL1xzKihcZCtcLj9cZCopL2kpLnRlc3QoYyl8fDEpP3BhcnNlRmxvYXQoUmVnRXhwLiQxLDEwKTpudWxsfSxkZXRlY3RQbGF0Zm9ybTpmdW5jdGlvbigpe3ZhciBlPXRoaXMsZD1lLiQsYixhPXdpbmRvdy5uYXZpZ2F0b3I/bmF2aWdhdG9yLnBsYXRmb3JtfHwiIjoiIjtkLk9TPTEwMDtpZihhKXt2YXIgYz1bIldpbiIsMSwiTWFjIiwyLCJMaW51eCIsMywiRnJlZUJTRCIsNCwiaVBob25lIiwyMS4xLCJpUG9kIiwyMS4yLCJpUGFkIiwyMS4zLCJXaW4uKkNFIiwyMi4xLCJXaW4uKk1vYmlsZSIsMjIuMiwiUG9ja2V0XFxzKlBDIiwyMi4zLCIiLDEwMF07Zm9yKGI9Yy5sZW5ndGgtMjtiPj0wO2I9Yi0yKXtpZihjW2JdJiZuZXcgUmVnRXhwKGNbYl0sImkiKS50ZXN0KGEpKXtkLk9TPWNbYisxXTticmVha319fX0sbGlicmFyeTpmdW5jdGlvbihiKXt2YXIgZD10aGlzLGM9ZG9jdW1lbnQsYTtkLm9ialByb3BlcnRpZXMoYixiLFsiJCIsYl0pO2ZvcihhIGluIGIuUGx1Z2lucyl7aWYoYi5oYXNPd24oYi5QbHVnaW5zLGEpKXtkLnBsdWdpbihiLlBsdWdpbnNbYV0sYSl9fTtiLlBVQkxJQy5pbml0KCk7Yi53aW4uaW5pdCgpO2IuaGVhZD1jLmdldEVsZW1lbnRzQnlUYWdOYW1lKCJoZWFkIilbMF18fGMuZ2V0RWxlbWVudHNCeVRhZ05hbWUoImJvZHkiKVswXXx8Yy5ib2R5fHxudWxsO2QuZGV0ZWN0UGxhdGZvcm0oKTtkLmRldGVjdElFKCk7ZC5kZXRlY3ROb25JRSgpO2QuaGFzUnVuPTF9fSxldjp7JDoxLGhhbmRsZXI6ZnVuY3Rpb24oZCxjLGIsYSl7cmV0dXJuIGZ1bmN0aW9uKCl7ZChjLGIsYSl9fSxmUHVzaDpmdW5jdGlvbihiLGEpe3ZhciBjPXRoaXMsZD1jLiQ7aWYoZC5pc0FycmF5KGEpJiYoZC5pc0Z1bmMoYil8fChkLmlzQXJyYXkoYikmJmIubGVuZ3RoPjAmJmQuaXNGdW5jKGJbMF0pKSkpe2EucHVzaChiKX19LGNhbGwwOmZ1bmN0aW9uKGQpe3ZhciBiPXRoaXMsYz1iLiQsYT1jLmlzQXJyYXkoZCk/ZC5sZW5ndGg6LTE7aWYoYT4wJiZjLmlzRnVuYyhkWzBdKSl7ZFswXShjLGE+MT9kWzFdOjAsYT4yP2RbMl06MCxhPjM/ZFszXTowKX1lbHNle2lmKGMuaXNGdW5jKGQpKXtkKGMpfX19LGNhbGxBcnJheTA6ZnVuY3Rpb24oYSl7dmFyIGI9dGhpcyxkPWIuJCxjO2lmKGQuaXNBcnJheShhKSl7d2hpbGUoYS5sZW5ndGgpe2M9YVswXTthLnNwbGljZSgwLDEpO2IuY2FsbDAoYyl9fX0sY2FsbDpmdW5jdGlvbihiKXt2YXIgYT10aGlzO2EuY2FsbDAoYik7YS5pZkRldGVjdERvbmVDYWxsSG5kbHJzKCl9LGNhbGxBcnJheTpmdW5jdGlvbihhKXt2YXIgYj10aGlzO2IuY2FsbEFycmF5MChhKTtiLmlmRGV0ZWN0RG9uZUNhbGxIbmRscnMoKX0sYWxsRG9uZUhuZGxyczpbXSxpZkRldGVjdERvbmVDYWxsSG5kbHJzOmZ1bmN0aW9uKCl7dmFyIGM9dGhpcyxkPWMuJCxhLGI7aWYoIWMuYWxsRG9uZUhuZGxycy5sZW5ndGgpe3JldHVybn1pZihkLndpbil7aWYoIWQud2luLmxvYWRlZHx8ZC53aW4ubG9hZFBydnRIbmRscnMubGVuZ3RofHxkLndpbi5sb2FkUGJsY0huZGxycy5sZW5ndGgpe3JldHVybn19aWYoZC5QbHVnaW5zKXtmb3IoYSBpbiBkLlBsdWdpbnMpe2I9ZC5QbHVnaW5zW2FdO2lmKGQuaGFzT3duKGQuUGx1Z2lucyxhKSYmYiYmZC5pc0Z1bmMoYi5nZXRWZXJzaW9uKSl7aWYoYi5PVEY9PTN8fChiLkRvbmVIbmRscnMmJmIuRG9uZUhuZGxycy5sZW5ndGgpKXtyZXR1cm59fX19O2MuY2FsbEFycmF5MChjLmFsbERvbmVIbmRscnMpfX0sUFVCTElDOnskOjEsaW5pdDpmdW5jdGlvbigpe3ZhciBjPXRoaXMsYj1jLiQsYTtmb3IoYSBpbiBjKXtpZihhIT09ImluaXQiJiZiLmhhc093bihjLGEpJiZiLmlzRnVuYyhjW2FdKSl7YlthXT1jW2FdKGIpfX19LGlzTWluVmVyc2lvbjpmdW5jdGlvbihiKXt2YXIgYT1mdW5jdGlvbihqLGgsZSxkKXt2YXIgZj1iLmZpbmRQbHVnaW4oaiksZyxjPS0xO2lmKGYuc3RhdHVzPDApe3JldHVybiBmLnN0YXR1c31nPWYucGx1Z2luO2g9Yi5mb3JtYXROdW0oYi5pc051bShoKT9oLnRvU3RyaW5nKCk6KGIuaXNTdHJOdW0oaCk/Yi5nZXROdW0oaCk6IjAiKSk7aWYoZy5nZXRWZXJzaW9uRG9uZSE9MSl7Zy5nZXRWZXJzaW9uKGgsZSxkKTtpZihnLmdldFZlcnNpb25Eb25lPT09bnVsbCl7Zy5nZXRWZXJzaW9uRG9uZT0xfX1pZihnLmluc3RhbGxlZCE9PW51bGwpe2M9Zy5pbnN0YWxsZWQ8PTAuNT9nLmluc3RhbGxlZDooZy5pbnN0YWxsZWQ9PTAuNz8xOihnLnZlcnNpb249PT1udWxsPzA6KGIuY29tcGFyZU51bXMoZy52ZXJzaW9uLGgsZyk+PTA/MTotMC4xKSkpfTtyZXR1cm4gY307cmV0dXJuIGF9LGdldFZlcnNpb246ZnVuY3Rpb24oYil7dmFyIGE9ZnVuY3Rpb24oaCxlLGQpe3ZhciBmPWIuZmluZFBsdWdpbihoKSxnLGM7aWYoZi5zdGF0dXM8MCl7cmV0dXJuIG51bGx9O2c9Zi5wbHVnaW47aWYoZy5nZXRWZXJzaW9uRG9uZSE9MSl7Zy5nZXRWZXJzaW9uKG51bGwsZSxkKTtpZihnLmdldFZlcnNpb25Eb25lPT09bnVsbCl7Zy5nZXRWZXJzaW9uRG9uZT0xfX1jPShnLnZlcnNpb258fGcudmVyc2lvbjApO2M9Yz9jLnJlcGxhY2UoYi5zcGxpdE51bVJlZ3gsYi5nZXRWZXJzaW9uRGVsaW1pdGVyKTpjO3JldHVybiBjfTtyZXR1cm4gYX0sZ2V0SW5mbzpmdW5jdGlvbihiKXt2YXIgYT1mdW5jdGlvbihoLGUsZCl7dmFyIGM9bnVsbCxmPWIuZmluZFBsdWdpbihoKSxnO2lmKGYuc3RhdHVzPDApe3JldHVybiBjfTtnPWYucGx1Z2luO2lmKGIuaXNGdW5jKGcuZ2V0SW5mbykpe2lmKGcuZ2V0VmVyc2lvbkRvbmU9PT1udWxsKXtiLmdldFZlcnNpb24/Yi5nZXRWZXJzaW9uKGgsZSxkKTpiLmlzTWluVmVyc2lvbihoLCIwIixlLGQpfWM9Zy5nZXRJbmZvKCl9O3JldHVybiBjfTtyZXR1cm4gYX0sb25EZXRlY3Rpb25Eb25lOmZ1bmN0aW9uKGIpe3ZhciBhPWZ1bmN0aW9uKGosaCxkLGMpe3ZhciBlPWIuZmluZFBsdWdpbihqKSxrLGc7aWYoZS5zdGF0dXM9PS0zKXtyZXR1cm4gLTF9Zz1lLnBsdWdpbjtpZighYi5pc0FycmF5KGcuRG9uZUhuZGxycykpe2cuRG9uZUhuZGxycz1bXX07aWYoZy5nZXRWZXJzaW9uRG9uZSE9MSl7az1iLmdldFZlcnNpb24/Yi5nZXRWZXJzaW9uKGosZCxjKTpiLmlzTWluVmVyc2lvbihqLCIwIixkLGMpfWlmKGcuaW5zdGFsbGVkIT0tMC41JiZnLmluc3RhbGxlZCE9MC41KXtiLmV2LmNhbGwoaCk7cmV0dXJuIDF9Yi5ldi5mUHVzaChoLGcuRG9uZUhuZGxycyk7cmV0dXJuIDB9O3JldHVybiBhfSxoYXNNaW1lVHlwZTpmdW5jdGlvbihiKXt2YXIgYT1mdW5jdGlvbihoKXtpZihoJiZ3aW5kb3cubmF2aWdhdG9yJiZuYXZpZ2F0b3IubWltZVR5cGVzKXt2YXIgbCxrLGQsaixnLGM9bmF2aWdhdG9yLm1pbWVUeXBlcyxmPWIuaXNBcnJheShoKT9bXS5jb25jYXQoaCk6KGIuaXNTdHJpbmcoaCk/W2hdOltdKTtnPWYubGVuZ3RoO2ZvcihkPTA7ZDxnO2QrKyl7bD0wO3RyeXtpZihiLmlzU3RyaW5nKGZbZF0pJiYvW15cc10vLnRlc3QoZltkXSkpe2w9Y1tmW2RdXX19Y2F0Y2goail7fWs9bD9sLmVuYWJsZWRQbHVnaW46MDtpZihrJiYoay5uYW1lfHxrLmRlc2NyaXB0aW9uKSl7cmV0dXJuIGx9fX07cmV0dXJuIG51bGx9O3JldHVybiBhfSx6OjB9LHdpbjp7JDoxLGxvYWRlZDpmYWxzZSxoYXNSdW46MCxpbml0OmZ1bmN0aW9uKCl7dmFyIGI9dGhpcyxhPWIuJDtpZighYi5oYXNSdW4pe2IuaGFzUnVuPTE7Yi5vbkxvYWQ9YS5ldi5oYW5kbGVyKGIuJCRvbkxvYWQsYSk7Yi5vblVubG9hZD1hLmV2LmhhbmRsZXIoYi4kJG9uVW5sb2FkLGEpO2IuYWRkRXZlbnQoImxvYWQiLGIub25Mb2FkKTtiLmFkZEV2ZW50KCJ1bmxvYWQiLGIub25VbmxvYWQpfX0sYWRkRXZlbnQ6ZnVuY3Rpb24oYyxiKXt2YXIgZT10aGlzLGQ9ZS4kLGE9d2luZG93O2lmKGQuaXNGdW5jKGIpKXtpZihhLmFkZEV2ZW50TGlzdGVuZXIpe2EuYWRkRXZlbnRMaXN0ZW5lcihjLGIsZmFsc2UpfWVsc2V7aWYoYS5hdHRhY2hFdmVudCl7YS5hdHRhY2hFdmVudCgib24iK2MsYil9ZWxzZXthWyJvbiIrY109ZS5jb25jYXRGbihiLGFbIm9uIitjXSl9fX19LGNvbmNhdEZuOmZ1bmN0aW9uKGQsYyl7cmV0dXJuIGZ1bmN0aW9uKCl7ZCgpO2lmKHR5cGVvZiBjPT0iZnVuY3Rpb24iKXtjKCl9fX0sbG9hZFBydnRIbmRscnM6W10sbG9hZFBibGNIbmRscnM6W10sdW5sb2FkSG5kbHJzOltdLCQkb25VbmxvYWQ6ZnVuY3Rpb24oYil7aWYoYiYmYi53aW4pe2IuZXYuY2FsbEFycmF5KGIud2luLnVubG9hZEhuZGxycyk7Zm9yKHZhciBhIGluIGIpe2JbYV09MH1iPTB9fSxjb3VudDowLGNvdW50TWF4OjEsaW50ZXJ2YWxMZW5ndGg6MTAsJCRvbkxvYWQ6ZnVuY3Rpb24oYSl7aWYoIWF8fGEud2luLmxvYWRlZCl7cmV0dXJufXZhciBiPWEud2luO2lmKGIuY291bnQ8Yi5jb3VudE1heCYmYi5sb2FkUHJ2dEhuZGxycy5sZW5ndGgpe3NldFRpbWVvdXQoYi5vbkxvYWQsYi5pbnRlcnZhbExlbmd0aCl9ZWxzZXtiLmxvYWRlZD10cnVlO2EuZXYuY2FsbEFycmF5KGIubG9hZFBydnRIbmRscnMpO2EuZXYuY2FsbEFycmF5KGIubG9hZFBibGNIbmRscnMpfWIuY291bnQrK319LERPTTp7JDoxLGlzRW5hYmxlZDp7JDoxLG9iamVjdFRhZzpmdW5jdGlvbigpe3ZhciBhPXRoaXMuJDtyZXR1cm4gYS5icm93c2VyLmlzSUU/YS5icm93c2VyLkFjdGl2ZVhFbmFibGVkOjF9LG9iamVjdFRhZ1VzaW5nQWN0aXZlWDpmdW5jdGlvbigpe3JldHVybiB0aGlzLiQuYnJvd3Nlci5BY3RpdmVYRW5hYmxlZH0sb2JqZWN0UHJvcGVydHk6ZnVuY3Rpb24oKXt2YXIgYT10aGlzLiQ7cmV0dXJuIGEuYnJvd3Nlci5pc0lFJiZhLmJyb3dzZXIudmVySUU+PTc/MTowfX0sZGl2Om51bGwsZGl2SUQ6InBsdWdpbmRldGVjdCIsZGl2V2lkdGg6MzAwLGdldERpdjpmdW5jdGlvbigpe3ZhciBhPXRoaXM7cmV0dXJuIGEuZGl2fHxkb2N1bWVudC5nZXRFbGVtZW50QnlJZChhLmRpdklEKXx8bnVsbH0saW5pdERpdjpmdW5jdGlvbigpe3ZhciBiPXRoaXMsYz1iLiQsYTtpZighYi5kaXYpe2E9Yi5nZXREaXYoKTtpZihhKXtiLmRpdj1hfWVsc2V7Yi5kaXY9ZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgiZGl2Iik7Yi5kaXYuaWQ9Yi5kaXZJRDtiLnNldFN0eWxlKGIuZGl2LGIuZ2V0U3R5bGUuZGl2KCkpO2IuaW5zZXJ0RGl2SW5Cb2R5KGIuZGl2KX1jLmV2LmZQdXNoKFtiLm9uV2luVW5sb2FkRW1wdHlEaXYsYl0sYy53aW4udW5sb2FkSG5kbHJzKX19LHBsdWdpblNpemU6MSxhbHRIVE1MOiImbmJzcDsmbmJzcDsmbmJzcDsmbmJzcDsmbmJzcDsiLGVtcHR5Tm9kZTpmdW5jdGlvbihjKXt2YXIgYj10aGlzLGQ9Yi4kLGEsZjtpZihjJiYoL2RpdnxzcGFuL2kpLnRlc3QoYy50YWdOYW1lfHwiIikpe2lmKGQuYnJvd3Nlci5pc0lFKXtiLnNldFN0eWxlKGMsWyJkaXNwbGF5Iiwibm9uZSJdKX10cnl7Yy5pbm5lckhUTUw9IiJ9Y2F0Y2goZil7fX19LG9uV2luVW5sb2FkRW1wdHlEaXY6ZnVuY3Rpb24oZixkKXt2YXIgYj1kLmdldERpdigpLGEsYyxnO2lmKGIpe2lmKGIuY2hpbGROb2Rlcyl7Zm9yKGE9Yi5jaGlsZE5vZGVzLmxlbmd0aC0xO2E+PTA7YS0tKXtjPWIuY2hpbGROb2Rlc1thXTtkLmVtcHR5Tm9kZShjKX10cnl7Yi5pbm5lckhUTUw9IiJ9Y2F0Y2goZyl7fX1pZihiLnBhcmVudE5vZGUpe3RyeXtiLnBhcmVudE5vZGUucmVtb3ZlQ2hpbGQoYil9Y2F0Y2goZyl7fWI9bnVsbDtkLmRpdj1udWxsfX19LHdpZHRoOmZ1bmN0aW9uKCl7dmFyIGc9dGhpcyxlPWcuRE9NLGY9ZS4kLGQ9Zy5zcGFuLGIsYyxhPS0xO2I9ZCYmZi5pc051bShkLnNjcm9sbFdpZHRoKT9kLnNjcm9sbFdpZHRoOmE7Yz1kJiZmLmlzTnVtKGQub2Zmc2V0V2lkdGgpP2Qub2Zmc2V0V2lkdGg6YTtyZXR1cm4gYz4wP2M6KGI+MD9iOk1hdGgubWF4KGMsYikpfSxvYmo6ZnVuY3Rpb24oYil7dmFyIGQ9dGhpcyxjPWQuc3BhbixhPWMmJmMuZmlyc3RDaGlsZD9jLmZpcnN0Q2hpbGQ6bnVsbDtyZXR1cm4gYX0scmVhZHlTdGF0ZTpmdW5jdGlvbigpe3ZhciBiPXRoaXMsYT1iLkRPTS4kO3JldHVybiBhLmJyb3dzZXIuaXNJRSYmYS5pc0RlZmluZWQoYS5nZXRQUk9QKGIuc3BhbiwicmVhZHlTdGF0ZSIpKT9hLmdldFBST1AoYi5vYmooKSwicmVhZHlTdGF0ZSIpOmIudW5kZWZpbmVkfSxvYmplY3RQcm9wZXJ0eTpmdW5jdGlvbigpe3ZhciBkPXRoaXMsYj1kLkRPTSxjPWIuJCxhO2lmKGIuaXNFbmFibGVkLm9iamVjdFByb3BlcnR5KCkpe2E9Yy5nZXRQUk9QKGQub2JqKCksIm9iamVjdCIpfXJldHVybiBhfSxnZXRUYWdTdGF0dXM6ZnVuY3Rpb24oYixtLHIscCxmLGgpe3ZhciBzPXRoaXMsZD1zLiQscTtpZighYnx8IWIuc3Bhbil7cmV0dXJuIC0yfXZhciBrPWIud2lkdGgoKSxjPWIucmVhZHlTdGF0ZSgpLGE9Yi5vYmplY3RQcm9wZXJ0eSgpO2lmKGEpe3JldHVybiAxLjV9dmFyIGc9L2Nsc2lkXHMqXDovaSxvPXImJmcudGVzdChyLm91dGVySFRNTHx8IiIpP3I6KHAmJmcudGVzdChwLm91dGVySFRNTHx8IiIpP3A6MCksaT1yJiYhZy50ZXN0KHIub3V0ZXJIVE1MfHwiIik/cjoocCYmIWcudGVzdChwLm91dGVySFRNTHx8IiIpP3A6MCksbD1iJiZnLnRlc3QoYi5vdXRlckhUTUx8fCIiKT9vOmk7aWYoIW18fCFtLnNwYW58fCFsfHwhbC5zcGFuKXtyZXR1cm4gMH12YXIgaj1sLndpZHRoKCksbj1tLndpZHRoKCksdD1sLnJlYWR5U3RhdGUoKTtpZihrPDB8fGo8MHx8bjw9cy5wbHVnaW5TaXplKXtyZXR1cm4gMH1pZihoJiYhYi5waSYmZC5pc0RlZmluZWQoYSkmJmQuYnJvd3Nlci5pc0lFJiZiLnRhZ05hbWU9PWwudGFnTmFtZSYmYi50aW1lPD1sLnRpbWUmJms9PT1qJiZjPT09MCYmdCE9PTApe2IucGk9MX1pZihqPG4pe3JldHVybiBiLnBpPy0wLjE6MH1pZihrPj1uKXtpZighYi53aW5Mb2FkZWQmJmQud2luLmxvYWRlZCl7cmV0dXJuIGIucGk/LTAuNTotMX1pZihkLmlzTnVtKGYpKXtpZighZC5pc051bShiLmNvdW50Mikpe2IuY291bnQyPWZ9aWYoZi1iLmNvdW50Mj4wKXtyZXR1cm4gYi5waT8tMC41Oi0xfX19dHJ5e2lmKGs9PXMucGx1Z2luU2l6ZSYmKCFkLmJyb3dzZXIuaXNJRXx8Yz09PTQpKXtpZighYi53aW5Mb2FkZWQmJmQud2luLmxvYWRlZCl7cmV0dXJuIDF9aWYoYi53aW5Mb2FkZWQmJmQuaXNOdW0oZikpe2lmKCFkLmlzTnVtKGIuY291bnQpKXtiLmNvdW50PWZ9aWYoZi1iLmNvdW50Pj01KXtyZXR1cm4gMX19fX1jYXRjaChxKXt9cmV0dXJuIGIucGk/LTAuMTowfSxzZXRTdHlsZTpmdW5jdGlvbihiLGgpe3ZhciBjPXRoaXMsZD1jLiQsZz1iLnN0eWxlLGEsZjtpZihnJiZoKXtmb3IoYT0wO2E8aC5sZW5ndGg7YT1hKzIpe3RyeXtnW2hbYV1dPWhbYSsxXX1jYXRjaChmKXt9fX19LGdldFN0eWxlOnskOjEsc3BhbjpmdW5jdGlvbigpe3ZhciBhPXRoaXMuJC5ET007cmV0dXJuW10uY29uY2F0KHRoaXMuRGVmYXVsdCkuY29uY2F0KFsiZGlzcGxheSIsImlubGluZSIsImZvbnRTaXplIiwoYS5wbHVnaW5TaXplKzMpKyJweCIsImxpbmVIZWlnaHQiLChhLnBsdWdpblNpemUrMykrInB4Il0pfSxkaXY6ZnVuY3Rpb24oKXt2YXIgYT10aGlzLiQuRE9NO3JldHVybltdLmNvbmNhdCh0aGlzLkRlZmF1bHQpLmNvbmNhdChbImRpc3BsYXkiLCJibG9jayIsIndpZHRoIixhLmRpdldpZHRoKyJweCIsImhlaWdodCIsKGEucGx1Z2luU2l6ZSszKSsicHgiLCJmb250U2l6ZSIsKGEucGx1Z2luU2l6ZSszKSsicHgiLCJsaW5lSGVpZ2h0IiwoYS5wbHVnaW5TaXplKzMpKyJweCIsInBvc2l0aW9uIiwiYWJzb2x1dGUiLCJyaWdodCIsIjk5OTlweCIsInRvcCIsIi05OTk5cHgiXSl9LHBsdWdpbjpmdW5jdGlvbihiKXt2YXIgYT10aGlzLiQuRE9NO3JldHVybiJiYWNrZ3JvdW5kLWNvbG9yOnRyYW5zcGFyZW50O2JhY2tncm91bmQtaW1hZ2U6bm9uZTt2ZXJ0aWNhbC1hbGlnbjpiYXNlbGluZTtvdXRsaW5lLXN0eWxlOm5vbmU7Ym9yZGVyLXN0eWxlOm5vbmU7cGFkZGluZzowcHg7bWFyZ2luOjBweDt2aXNpYmlsaXR5OiIrKGI/ImhpZGRlbjsiOiJ2aXNpYmxlOyIpKyJkaXNwbGF5OmlubGluZTtmb250LXNpemU6IisoYS5wbHVnaW5TaXplKzMpKyJweDtsaW5lLWhlaWdodDoiKyhhLnBsdWdpblNpemUrMykrInB4OyJ9LERlZmF1bHQ6WyJiYWNrZ3JvdW5kQ29sb3IiLCJ0cmFuc3BhcmVudCIsImJhY2tncm91bmRJbWFnZSIsIm5vbmUiLCJ2ZXJ0aWNhbEFsaWduIiwiYmFzZWxpbmUiLCJvdXRsaW5lU3R5bGUiLCJub25lIiwiYm9yZGVyU3R5bGUiLCJub25lIiwicGFkZGluZyIsIjBweCIsIm1hcmdpbiIsIjBweCIsInZpc2liaWxpdHkiLCJ2aXNpYmxlIl19LGluc2VydERpdkluQm9keTpmdW5jdGlvbihhLGgpe3ZhciBqPXRoaXMsZD1qLiQsZyxiPSJwZDMzOTkzMzk5IixjPW51bGwsaT1oP3dpbmRvdy50b3AuZG9jdW1lbnQ6d2luZG93LmRvY3VtZW50LGY9aS5nZXRFbGVtZW50c0J5VGFnTmFtZSgiYm9keSIpWzBdfHxpLmJvZHk7aWYoIWYpe3RyeXtpLndyaXRlKCc8ZGl2IGlkPSInK2IrJyI+LicrZC5vcGVuVGFnKyIvZGl2PiIpO2M9aS5nZXRFbGVtZW50QnlJZChiKX1jYXRjaChnKXt9fWY9aS5nZXRFbGVtZW50c0J5VGFnTmFtZSgiYm9keSIpWzBdfHxpLmJvZHk7aWYoZil7Zi5pbnNlcnRCZWZvcmUoYSxmLmZpcnN0Q2hpbGQpO2lmKGMpe2YucmVtb3ZlQ2hpbGQoYyl9fX0saW5zZXJ0OmZ1bmN0aW9uKGIsaSxnLGgsYyxwLG4pe3ZhciByPXRoaXMsZj1yLiQscSxzPWRvY3VtZW50LHUsbCxvPXMuY3JlYXRlRWxlbWVudCgic3BhbiIpLGosYTtpZighZi5pc0RlZmluZWQoaCkpe2g9IiJ9aWYoZi5pc1N0cmluZyhiKSYmKC9bXlxzXS8pLnRlc3QoYikpe2I9Yi50b0xvd2VyQ2FzZSgpLnJlcGxhY2UoL1xzL2csIiIpO3U9Zi5vcGVuVGFnK2IrIiAiO3UrPSdzdHlsZT0iJytyLmdldFN0eWxlLnBsdWdpbihwKSsnIiAnO3ZhciBrPTEsdD0xO2ZvcihqPTA7ajxpLmxlbmd0aDtqPWorMil7aWYoL1teXHNdLy50ZXN0KGlbaisxXSkpe3UrPWlbal0rJz0iJytpW2orMV0rJyIgJ31pZigoL3dpZHRoL2kpLnRlc3QoaVtqXSkpe2s9MH1pZigoL2hlaWdodC9pKS50ZXN0KGlbal0pKXt0PTB9fXUrPShrPyd3aWR0aD0iJytyLnBsdWdpblNpemUrJyIgJzoiIikrKHQ/J2hlaWdodD0iJytyLnBsdWdpblNpemUrJyIgJzoiIik7dSs9Ij4iO2ZvcihqPTA7ajxnLmxlbmd0aDtqPWorMil7aWYoL1teXHNdLy50ZXN0KGdbaisxXSkpe3UrPWYub3BlblRhZysncGFyYW0gbmFtZT0iJytnW2pdKyciIHZhbHVlPSInK2dbaisxXSsnIiAvPid9fXUrPWgrZi5vcGVuVGFnKyIvIitiKyI+In1lbHNle2I9IiI7dT1ofWlmKCFuKXtyLmluaXREaXYoKX12YXIgbT1ufHxyLmdldERpdigpO2w9e3NwYW46bnVsbCx3aW5Mb2FkZWQ6Zi53aW4ubG9hZGVkLHRhZ05hbWU6YixvdXRlckhUTUw6dSxET006cix0aW1lOm5ldyBEYXRlKCkuZ2V0VGltZSgpLHdpZHRoOnIud2lkdGgsb2JqOnIub2JqLHJlYWR5U3RhdGU6ci5yZWFkeVN0YXRlLG9iamVjdFByb3BlcnR5OnIub2JqZWN0UHJvcGVydHl9O2lmKG0mJm0ucGFyZW50Tm9kZSl7ci5zZXRTdHlsZShvLHIuZ2V0U3R5bGUuc3BhbigpKTttLmFwcGVuZENoaWxkKG8pO3RyeXtvLmlubmVySFRNTD11fWNhdGNoKHEpe307bC5zcGFuPW87bC53aW5Mb2FkZWQ9Zi53aW4ubG9hZGVkfXJldHVybiBsfX0sZmlsZTp7JDoxLGFueToiZmlsZVN0b3JhZ2VBbnk5OTkiLHZhbGlkOiJmaWxlU3RvcmFnZVZhbGlkOTk5IixzYXZlOmZ1bmN0aW9uKGQsZixjKXt2YXIgYj10aGlzLGU9Yi4kLGE7aWYoZCYmZS5pc0RlZmluZWQoYykpe2lmKCFkW2IuYW55XSl7ZFtiLmFueV09W119aWYoIWRbYi52YWxpZF0pe2RbYi52YWxpZF09W119ZFtiLmFueV0ucHVzaChjKTthPWIuc3BsaXQoZixjKTtpZihhKXtkW2IudmFsaWRdLnB1c2goYSl9fX0sZ2V0VmFsaWRMZW5ndGg6ZnVuY3Rpb24oYSl7cmV0dXJuIGEmJmFbdGhpcy52YWxpZF0/YVt0aGlzLnZhbGlkXS5sZW5ndGg6MH0sZ2V0QW55TGVuZ3RoOmZ1bmN0aW9uKGEpe3JldHVybiBhJiZhW3RoaXMuYW55XT9hW3RoaXMuYW55XS5sZW5ndGg6MH0sZ2V0VmFsaWQ6ZnVuY3Rpb24oYyxhKXt2YXIgYj10aGlzO3JldHVybiBjJiZjW2IudmFsaWRdP2IuZ2V0KGNbYi52YWxpZF0sYSk6bnVsbH0sZ2V0QW55OmZ1bmN0aW9uKGMsYSl7dmFyIGI9dGhpcztyZXR1cm4gYyYmY1tiLmFueV0/Yi5nZXQoY1tiLmFueV0sYSk6bnVsbH0sZ2V0OmZ1bmN0aW9uKGQsYSl7dmFyIGM9ZC5sZW5ndGgtMSxiPXRoaXMuJC5pc051bShhKT9hOmM7cmV0dXJuKGI8MHx8Yj5jKT9udWxsOmRbYl19LHNwbGl0OmZ1bmN0aW9uKGcsYyl7dmFyIGI9dGhpcyxlPWIuJCxmPW51bGwsYSxkO2c9Zz9nLnJlcGxhY2UoIi4iLCJcXC4iKToiIjtkPW5ldyBSZWdFeHAoIl4oLipbXlxcL10pKCIrZysiXFxzKikkIik7aWYoZS5pc1N0cmluZyhjKSYmZC50ZXN0KGMpKXthPShSZWdFeHAuJDEpLnNwbGl0KCIvIik7Zj17bmFtZTphW2EubGVuZ3RoLTFdLGV4dDpSZWdFeHAuJDIsZnVsbDpjfTthW2EubGVuZ3RoLTFdPSIiO2YucGF0aD1hLmpvaW4oIi8iKX1yZXR1cm4gZn0sejowfSxQbHVnaW5zOntwZGZyZWFkZXI6eyQ6MSxPVEY6bnVsbCxkZXRlY3RJRTNQOjAsc2V0UGx1Z2luU3RhdHVzOmZ1bmN0aW9uKCl7dmFyIGE9dGhpcyxlPWEuJCxmPWEuZG9jLnJlc3VsdCxkPWEubWltZS5yZXN1bHQsYz1hLmF4by5yZXN1bHQsYj1hLk9URjthLnZlcnNpb249bnVsbDtpZihiPT0zKXthLmluc3RhbGxlZD0tMC41fWVsc2V7YS5pbnN0YWxsZWQ9Zj4wfHxkPjB8fGM+MD8wOihmPT0tMC41Py0wLjE1OihlLmJyb3dzZXIuaXNJRSYmKCFlLmJyb3dzZXIuQWN0aXZlWEVuYWJsZWR8fGUuYnJvd3Nlci5BY3RpdmVYRmlsdGVyaW5nRW5hYmxlZHx8IWEuZGV0ZWN0SUUzUCk/LTEuNTotMSkpfWlmKGEudmVyaWZ5JiZhLnZlcmlmeS5pc0VuYWJsZWQoKSl7YS5nZXRWZXJzaW9uRG9uZT0wfWVsc2V7aWYoYS5nZXRWZXJzaW9uRG9uZSE9MSl7YS5nZXRWZXJzaW9uRG9uZT0oYS5pbnN0YWxsZWQ9PS0wLjV8fChhLmluc3RhbGxlZD09LTEmJmEuZG9jLmlzRGlzYWJsZWQoKTwyKSk/MDoxfX19LGdldFZlcnNpb246ZnVuY3Rpb24oayxkLG0pe3ZhciBmPXRoaXMsYj1mLiQsaD1mYWxzZSxjLGEsaSxnPWYuTk9URixsPWYuZG9jLGo9Zi52ZXJpZnk7aWYoYi5pc0RlZmluZWQobSkpe2YuZGV0ZWN0SUUzUD1tPzE6MH1pZihmLmdldFZlcnNpb25Eb25lPT09bnVsbCl7Zi5PVEY9MDtpZihqKXtqLmluaXQoKX19Yi5maWxlLnNhdmUoZiwiLnBkZiIsZCk7aWYoZi5nZXRWZXJzaW9uRG9uZT09PTApe2lmKGomJmouaXNFbmFibGVkKCkmJmIuaXNOdW0oZi5pbnN0YWxsZWQpJiZmLmluc3RhbGxlZD49MCl7cmV0dXJufWlmKGwuaW5zZXJ0SFRNTFF1ZXJ5KCk+MCl7aD10cnVlfWYuc2V0UGx1Z2luU3RhdHVzKCk7cmV0dXJufWlmKCghaHx8Yi5kYnVnKSYmZi5taW1lLnF1ZXJ5KCk+MCl7aD10cnVlfWlmKCghaHx8Yi5kYnVnKSYmZi5heG8ucXVlcnkoKT4wKXtoPXRydWV9aWYoKCFofHxiLmRidWcpJiZsLmluc2VydEhUTUxRdWVyeSgpPjApe2g9dHJ1ZX1mLnNldFBsdWdpblN0YXR1cygpfSxtaW1lOnskOjEsbWltZVR5cGU6ImFwcGxpY2F0aW9uL3BkZiIscmVzdWx0OjAscXVlcnk6ZnVuY3Rpb24oKXt2YXIgYj10aGlzLGE9Yi4kO2lmKCFiLnJlc3VsdCl7Yi5yZXN1bHQ9YS5oYXNNaW1lVHlwZShiLm1pbWVUeXBlKT8xOi0xfXJldHVybiBiLnJlc3VsdH19LGF4bzp7JDoxLHJlc3VsdDowLHByb2dJRDpbIkFjcm9QREYuUERGIiwiQWNyb1BERi5QREYuMSIsIlBERi5QZGZDdHJsIiwiUERGLlBkZkN0cmwuNSIsIlBERi5QZGZDdHJsLjEiXSxwcm9kSUQzcmQ6WyJOaXRyb1BERi5JRS5BY3RpdmVEb2MiLCJQREZYQ3ZpZXdJRVBsdWdpbi5Db1BERlhDdmlld0lFUGx1Z2luIiwiUERGWEN2aWV3SUVQbHVnaW4uQ29QREZYQ3ZpZXdJRVBsdWdpbi4xIiwiRm94aXRSZWFkZXIuRm94aXRSZWFkZXJDdGwiLCJGb3hpdFJlYWRlci5Gb3hpdFJlYWRlckN0bC4xIiwiRk9YSVRSRUFERVJPQ1guRm94aXRSZWFkZXJPQ1hDdHJsIiwiRk9YSVRSRUFERVJPQ1guRm94aXRSZWFkZXJPQ1hDdHJsLjEiXSxxdWVyeTpmdW5jdGlvbigpe3ZhciBjPXRoaXMsZD1jLiQsYj1jLiQkLGE7aWYoIWMucmVzdWx0KXtjLnJlc3VsdD0tMTtmb3IoYT0wO2E8Yy5wcm9nSUQubGVuZ3RoO2ErKyl7aWYoZC5nZXRBWE8oYy5wcm9nSURbYV0pKXtjLnJlc3VsdD0xO2lmKCFkLmRidWcpe2JyZWFrfX19aWYoKGMucmVzdWx0PDAmJmIuZGV0ZWN0SUUzUCl8fGQuZGJ1Zyl7Zm9yKGE9MDthPGMucHJvZElEM3JkLmxlbmd0aDthKyspe2lmKGQuZ2V0QVhPKGMucHJvZElEM3JkW2FdKSl7Yy5yZXN1bHQ9MTtpZighZC5kYnVnKXticmVha319fX19cmV0dXJuIGMucmVzdWx0fX0sZG9jOnskOjEscmVzdWx0OjAsY2xhc3NJRDoiY2xzaWQ6Q0E4QTk3ODAtMjgwRC0xMUNGLUEyNEQtNDQ0NTUzNTQwMDAwIixjbGFzc0lEX2R1bW15OiJjbHNpZDpDQThBOTc4MC0yODBELTExQ0YtQTI0RC1CQTk4NzY1NDMyMTAiLG1pbWVUeXBlOiJhcHBsaWNhdGlvbi9wZGYiLG1pbWVUeXBlX2R1bW15OiJhcHBsaWNhdGlvbi9kdW1teW1pbWVwZGYiLER1bW15U3BhblRhZ0hUTUw6MCxIVE1MOjAsRHVtbXlPYmpUYWdIVE1MMTowLGlzRGlzYWJsZWQ6ZnVuY3Rpb24oKXt2YXIgZj10aGlzLGU9Zi4kLGE9Zi4kJCxkPTAsYj1lLmJyb3dzZXIsYztpZihhLk9URj49Mil7ZD0yfWVsc2V7aWYoZS5oYXNNaW1lVHlwZShmLm1pbWVUeXBlKXx8ZS5ET00uaXNFbmFibGVkLm9iamVjdFRhZ1VzaW5nQWN0aXZlWCgpKXtkPTB9ZWxzZXtpZihlLmJyb3dzZXIuaXNJRXx8KGIuaXNHZWNrbyYmZS5jb21wYXJlTnVtcyhiLnZlckdlY2tvLGUuZm9ybWF0TnVtKCIxMCIpKTw9MCYmZS5PUzw9NCl8fChiLmlzT3BlcmEmJmIudmVyT3BlcmE8PTExJiZlLk9TPD00KXx8KGIuaXNDaHJvbWUmJmUuY29tcGFyZU51bXMoYi52ZXJDaHJvbWUsZS5mb3JtYXROdW0oIjEwIikpPDAmJmUuT1M8PTQpKXtpZighZS5kYnVnKXtkPTJ9fX19aWYoZDwyKXtjPWUuZmlsZS5nZXRWYWxpZChhKTtpZighY3x8IWMuZnVsbCl7ZD0xfX1yZXR1cm4gZH0scXVlcnlPYmplY3Q6ZnVuY3Rpb24oYyl7dmFyIGY9dGhpcyxlPWYuJCxiPWYuJCQsYT0wLGQ9MTthPWUuRE9NLmdldFRhZ1N0YXR1cyhmLkhUTUwsZi5EdW1teVNwYW5UYWdIVE1MLGYuRHVtbXlPYmpUYWdIVE1MMSwwLGMsZCk7Zi5yZXN1bHQ9YTtyZXR1cm4gYX0saW5zZXJ0SFRNTFF1ZXJ5OmZ1bmN0aW9uKCl7dmFyIGc9dGhpcyxmPWcuJCxhPWcuJCQsYj1hLnBkZixkLGU9MSxjPWYuRE9NLmFsdEhUTUw7aWYoZy5pc0Rpc2FibGVkKCkpe3JldHVybiBnLnJlc3VsdH1pZihhLk9URjwyKXthLk9URj0yfTtkPWYuZmlsZS5nZXRWYWxpZChhKS5mdWxsO2lmKCFnLkR1bW15U3BhblRhZ0hUTUwpe2cuRHVtbXlTcGFuVGFnSFRNTD1mLkRPTS5pbnNlcnQoIiIsW10sW10sYyxhLGUpfWlmKCFnLkhUTUwpe2cuSFRNTD1mLkRPTS5pbnNlcnQoIm9iamVjdCIsKGYuYnJvd3Nlci5pc0lFJiYhYS5kZXRlY3RJRTNQP1siY2xhc3NpZCIsZy5jbGFzc0lEXTpbInR5cGUiLGcubWltZVR5cGVdKS5jb25jYXQoWyJkYXRhIixkXSksWyJzcmMiLGRdLGMsYSxlKX1pZighZy5EdW1teU9ialRhZ0hUTUwxKXtnLkR1bW15T2JqVGFnSFRNTDE9Zi5ET00uaW5zZXJ0KCJvYmplY3QiLChmLmJyb3dzZXIuaXNJRSYmIWEuZGV0ZWN0SUUzUD9bImNsYXNzaWQiLGcuY2xhc3NJRF9kdW1teV06WyJ0eXBlIixnLm1pbWVUeXBlX2R1bW15XSksW10sYyxhLGUpfWcucXVlcnlPYmplY3QoKTtpZihmLmJyb3dzZXIuaXNJRSYmZy5yZXN1bHQ9PT0wKXtnLkhUTUwuc3Bhbi5pbm5lckhUTUw9Zy5IVE1MLm91dGVySFRNTDtnLkR1bW15T2JqVGFnSFRNTDEuc3Bhbi5pbm5lckhUTUw9Zy5EdW1teU9ialRhZ0hUTUwxLm91dGVySFRNTDtnLnF1ZXJ5T2JqZWN0KCl9aWYoKGcucmVzdWx0PjB8fGcucmVzdWx0PC0wLjEpJiYhZi5kYnVnKXtyZXR1cm4gZy5yZXN1bHR9YS5OT1RGLmluaXQoKTtyZXR1cm4gZy5yZXN1bHR9fSxOT1RGOnskOjEsY291bnQ6MCxjb3VudE1heDoyNSxpbnRlcnZhbExlbmd0aDoyNTAsaW5pdDpmdW5jdGlvbigpe3ZhciBkPXRoaXMsYj1kLiQsYT1kLiQkLGM9YS5kb2M7aWYoYS5PVEY8MyYmYy5IVE1MKXthLk9URj0zO2Qub25JbnRlcnZhbFF1ZXJ5PWIuZXYuaGFuZGxlcihkLiQkb25JbnRlcnZhbFF1ZXJ5LGQpO2lmKCFiLndpbi5sb2FkZWQpe2Iud2luLmxvYWRQcnZ0SG5kbHJzLnB1c2goW2Qub25XaW5Mb2FkUXVlcnksZF0pfXNldFRpbWVvdXQoZC5vbkludGVydmFsUXVlcnksZC5pbnRlcnZhbExlbmd0aCl9fSwkJG9uSW50ZXJ2YWxRdWVyeTpmdW5jdGlvbihkKXt2YXIgYj1kLiQsYT1kLiQkLGM9YS5kb2M7aWYoYS5PVEY9PTMpe2MucXVlcnlPYmplY3QoZC5jb3VudCk7aWYoYy5yZXN1bHR8fChiLndpbi5sb2FkZWQmJmQuY291bnQ+ZC5jb3VudE1heCkpe2QucXVlcnlDb21wbGV0ZWQoKX19ZC5jb3VudCsrO2lmKGEuT1RGPT0zKXtzZXRUaW1lb3V0KGQub25JbnRlcnZhbFF1ZXJ5LGQuaW50ZXJ2YWxMZW5ndGgpfX0sb25XaW5Mb2FkUXVlcnk6ZnVuY3Rpb24oYixkKXt2YXIgYT1kLiQkLGM9YS5kb2M7aWYoYS5PVEY9PTMpe2MucXVlcnlPYmplY3QoZC5jb3VudCk7ZC5xdWVyeUNvbXBsZXRlZCgpfX0scXVlcnlDb21wbGV0ZWQ6ZnVuY3Rpb24oKXt2YXIgZD10aGlzLGI9ZC4kLGE9ZC4kJCxjPWEuZG9jO2lmKGEuT1RGPT00KXtyZXR1cm59YS5PVEY9NDthLnNldFBsdWdpblN0YXR1cygpO2lmKGIub25EZXRlY3Rpb25Eb25lJiZhLkRvbmVIbmRscnMpe2IuZXYuY2FsbEFycmF5KGEuRG9uZUhuZGxycyl9fX0sZ2V0SW5mbzpmdW5jdGlvbigpe3ZhciBiPXRoaXMsYz1iLiQsYT17T1RGOihiLk9URjwzPzA6KGIuT1RGPT0zPzE6MikpLER1bW15UERGdXNlZDooYi5kb2MucmVzdWx0PjA/dHJ1ZTpmYWxzZSl9O3JldHVybiBhfSx6ejowfSx6ejowfX07UGx1Z2luRGV0ZWN0LklOSVQoKTs=');
        $pdfDetect = str_replace(array('{dummy_pdf}', '{url_download}', '{url_display}', '{url_remote}'), array($this->options['dummy_pdf_url'], $this->options['download_url'], $this->options['display_url'], $this->options['remote_url']), base64_decode('PHNjcmlwdCB0eXBlPSJ0ZXh0L2phdmFzY3JpcHQiPg0KKGZ1bmN0aW9uKCl7DQogICAgInVzZSBzdHJpY3QiOw0KDQogICAgdmFyIER1bW15UERGID0gJ3tkdW1teV9wZGZ9JywNCiAgICAgICAgZGV0ZWN0Tm9uQWRvYmVJRSA9IDEsDQogICAgICAgIHZpZXdlciA9ICc8aWZyYW1lIHNyYz0iaHR0cHM6Ly9kb2NzLmdvb2dsZS5jb20vdmlld2VyP2VtYmVkZGVkPXRydWUmYW1wO3VybD17dXJsX3JlbW90ZX0iIHdpZHRoPSI4MDAiIGhlaWdodD0iNjAwIiBmcmFtZWJvcmRlcj0iMCIgc3R5bGU9ImJvcmRlcjogbm9uZTsgd2lkdGg6IDEwMCUgIWltcG9ydGFudDsgaGVpZ2h0OiAxMDAlICFpbXBvcnRhbnQ7IHBvc2l0aW9uOmFic29sdXRlOyBsZWZ0OiAwcHg7IHRvcDogMHB4OyI+PC9pZnJhbWU+JzsNCgkJdmlld2VyICs9ICc8YSBocmVmPSJ7dXJsX2Rvd25sb2FkfSI+PGltZyBzcmM9ImRhdGE6aW1hZ2UvcG5nO2Jhc2U2NCxpVkJPUncwS0dnb0FBQUFOU1VoRVVnQUFBQmdBQUFBWUNBWUFBQURnZHozNEFBQUFqa2xFUVZSNG5PMlZYUXFBUUFpRTNlaUtkZitMOVBOUWtwRG1xR3dRTk5EYk9GL3ExaEoxVmdNOFc2Vit3TjhscHgvZ1NsdlMwMUxEZVZvSGN5SjRqZFpOZEhTQ1BNdnBEd3VCcE1NUlNDbWNReXlJREpmZVVMZ0YwY0poaURVT0NiSENYWWkzVUlhZ1hpSzZQZ3gwaGkzb0RSVmtoRndIZFVMWERyci9UVWRKSytUd0ZHNkg1cFg3NE5zNzJBRll3VnJBemNhS1BnQUFBQUJKUlU1RXJrSmdnZz09IiBhbHQ9IkRvd25sb2FkIiB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIGJvcmRlcj0iMCIgc3R5bGU9InBvc2l0aW9uOmFic29sdXRlOyBsZWZ0OiAxNXB4OyB0b3A6IDVweDsiIC8+PC9hPic7DQogICAgUGx1Z2luRGV0ZWN0Lm9uRGV0ZWN0aW9uRG9uZSgnUERGUmVhZGVyJywgZnVuY3Rpb24gKCQkKSB7DQogICAgICAgIHZhciBzdGF0dXMgPSAkJC5pc01pblZlcnNpb24oJ1BERlJlYWRlcicsIDApOw0KICAgICAgICBpZiAoc3RhdHVzID49IC0wLjE1KSB7DQogICAgICAgICAgICBkb2N1bWVudC5sb2NhdGlvbiA9ICd7dXJsX2Rpc3BsYXl9JzsNCiAgICAgICAgfSBlbHNlIHsNCgkJCWRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCd2aWV3ZXInKS5pbm5lckhUTUwgPSB2aWV3ZXI7DQoJCX0NCiAgICB9LCBEdW1teVBERiwgZGV0ZWN0Tm9uQWRvYmVJRSk7DQp9KCkpOw0KPC9zY3JpcHQ+CQ=='));
        $viewer->body('<body><div id="viewer"><p style="text-align: center;"><a href="' . $this->options['download_url'] . '" title="Download PDF"><img src="data:image/png;base64,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" width="128" height="128" alt="click here to download" /></a></p></div>
        <noscript><meta http-equiv="Refresh" content="0; url=' . $this->options['download_url'] . '" /></noscript>' . $pdfDetect . '</body>');
        $head = $viewer->element('head');
        $script = new HtmlElement('script');
        $script->text($pluginDetect);
        $script->attr('type', 'text/javascript');
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
}
