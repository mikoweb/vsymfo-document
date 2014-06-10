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

use vSymfo\Component\Document\ResourcesInterface;
use vSymfo\Component\Document\Element\TxtElement;
use vSymfo\Component\Document\Element\HtmlElement;
use vSymfo\Component\Document\Resources\JavaScriptResourceManager;
use vSymfo\Component\Document\Resources\StyleSheetResourceManager;
use vSymfo\Component\Document\ResourceGroups;
use JShrink\Minifier;

/**
 * Dokument HTML
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Type
 */
class HtmlDocument extends DocumentAbstract
{
    /**
     * @var TxtElement
     */
    private $doctype = null;

    /**
     * @var string
     */
    private $body = '';

    /**
     * @var HtmlElement
     */
    private $html = null;

    /**
     * @var HtmlElement
     */
    private $head = null;

    /**
     * @var HtmlElement
     */
    private $title = null;

    /**
     * @var HtmlElement
     */
    private $script = null;

    /**
     * @var HtmlElement
     */
    private $viewport = null;

    /**
     * @var HtmlElement
     */
    private $charset = null;

    /**
     * @var HtmlElement
     */
    private $author = null;

    /**
     * @var HtmlElement
     */
    private $authorUrl = null;

    /**
     * @var HtmlElement
     */
    private $description = null;

    /**
     * @var HtmlElement
     */
    private $keywords = null;

    /**
     * @var HtmlElement
     */
    private $xua_compatible = null;

    /**
     * @var JavaScriptResourceManager
     */
    private $javaScript = null;

    /**
     * @var StyleSheetResourceManager
     */
    private $styleSheet = null;

    /**
     * @var \Closure
     */
    private $scriptOutput = null;

    public function __construct()
    {
        $this->doctype = new TxtElement('<!DOCTYPE html>');
        $this->html = new HtmlElement('html');
        $this->head = new HtmlElement('head');
        $this->title = new HtmlElement('title');
        $this->title->insertTo($this->head);
        $this->charset = new HtmlElement('meta');
        $this->charset
            ->attr('charset', 'utf-8')
            ->insertTo($this->head)
        ;
        $this->viewport = new HtmlElement('meta');
        $this->viewport
            ->attr('name', 'viewport')
            ->attr('content', 'width=device-width, initial-scale=1.0')
            ->insertTo($this->head)
        ;
        $this->xua_compatible = new HtmlElement('meta');
        $this->xua_compatible
            ->attr('http-equiv', 'X-UA-Compatible')
            ->attr('content', 'IE=Edge,chrome=1')
            ->insertTo($this->head)
        ;
        $this->script = new HtmlElement('script');
        $this->script->attr('type', 'text/javascript');
        $this->author = new HtmlElement('meta');
        $this->authorUrl = new HtmlElement('link');
        $this->description = new HtmlElement('meta');
        $this->keywords = new HtmlElement('meta');
        $this->javaScript = new JavaScriptResourceManager(new ResourceGroups());
        $this->styleSheet = new StyleSheetResourceManager(new ResourceGroups());
        $this->scriptOutput = function(JavaScriptResourceManager $manager) {
            return $manager->render('html');
        };
    }

    /**
     * ustaw wyjście skryptów
     * @param \Closure $output
     * @throws \Exception
     */
    public function setScriptOutput(\Closure $output)
    {
        $reflection = new \ReflectionFunction($output);
        $args = $reflection->getParameters();
        if (isset($args[0]) && is_object($args[0]->getClass())
            && $args[0]->getClass()
                ->implementsInterface('vSymfo\Component\Document\Resources\JavaScriptResourceManager')
        ) {
            $this->scriptOutput = $output;
        } else {
            throw new \Exception('not allowed Closure');
        }
    }

    /**
     * zasoby
     * @param string $name
     * @return ResourcesInterface
     * @throws \Exception
     */
    public function resources($name)
    {
        switch ($name) {
            case 'javascript':
                return $this->javaScript;
            case 'stylesheet':
                return $this->styleSheet;
        }

        throw new \Exception('Resource ' . $name . ' not found.');
    }

    /**
     * elementy
     * @param string $name
     * @return HtmlElement|TxtElement
     * @throws \Exception
     */
    public function element($name)
    {
        switch ($name) {
            case 'script':
                return $this->script;
            case 'head':
                return $this->head;
            case 'viewport':
                return $this->viewport;
            case 'html':
                return $this->html;
            case 'charset':
                return $this->charset;
            case 'doctype':
                return $this->doctype;
            case 'xua_compatible':
                return $this->xua_compatible;
        }

        throw new \Exception('Element ' . $name . ' not found.');
    }

    /**
     * tytuł
     * @param string $set
     * @param integer $mode
     * @param string $separator
     * @return string
     */
    public function title($set = null, $mode = self::TITLE_ONLY_TITLE, $separator = '-')
    {
        $title = parent::title($set, $mode, $separator);
        $this->title->text(htmlspecialchars($title));
        return $title;
    }

    /**
     * wstaw określony element do nagłówka
     * tylko wtedy gdy nie jest pusty
     * @param HtmlElement $el
     * @param string $set
     * @param callable $update
     */
    private function insertToHead(HtmlElement $el, $set, \Closure $update)
    {
        if (is_string($set)) {
            if (!empty($set)) {
                $el->insertTo($this->head);
            } else {
                $new = new HtmlElement($el->name());
                $el->destroy($el);
                $el = $new;
            }

            $update($el);
        }
    }

    /**
     * autor
     * @param string $set
     * @return string
     */
    public function author($set = null)
    {
        $author = parent::author($set);
        $this->insertToHead($this->author, $set,
            function(HtmlElement $el) use($author) {
                $el->attr('name', 'author');
                $el->attr('content', htmlspecialchars($author));
            });

        return $author;
    }

    /**
     * strona autora
     * @param string $set
     * @return string
     */
    public function authorUrl($set = null)
    {
        $url = parent::authorUrl($set);
        $this->insertToHead($this->authorUrl, $set,
            function(HtmlElement $el) use($url) {
                $el->attr('rel', 'author');
                $el->attr('href', $url);
            });

        return $url;
    }

    /**
     * opis
     * @param string $set
     * @return string
     */
    public function description($set = null)
    {
        $desc = parent::author($set);
        $this->insertToHead($this->description, $set,
            function(HtmlElement $el) use($desc) {
                $el->attr('name', 'description');
                $el->attr('content', strip_tags($desc));
            });

        return $desc;
    }

    /**
     * słowa kluczowe
     * @param string $set
     * @return string
     */
    public function keywords($set = null)
    {
        $words = parent::author($set);
        $this->insertToHead($this->keywords, $set,
            function(HtmlElement $el) use($words) {
                $el->attr('name', 'keywords');
                $el->attr('content', strip_tags($words));
            });

        return $words;
    }

    /**
     * treść
     * @param string
     * @return string
     */
    public function body($set = null)
    {
        if (is_string($set)) {
            $this->body = $set;
        }

        return $this->body;
    }

    /**
     * @return string
     */
    public function render()
    {
        $output = preg_match('/^<!DOCTYPE\s.*>$/', trim($this->doctype->render()))
            ? trim($this->doctype->render()) . PHP_EOL
            : '<!DOCTYPE html>' . PHP_EOL;

        preg_match('/<html.*?>/', $this->html->render(), $result);
        $output .= isset($result[0])
            ? $result[0] . PHP_EOL
            : '<html>' . PHP_EOL;

        $output .= substr($this->head->render(), 0, -7);

        if ($this->styleSheet->length()) {
            $output .= $this->styleSheet->render('html') . PHP_EOL;
        }

        if ($this->javaScript->length()) {
            $tmp = call_user_func($this->scriptOutput, $this->javaScript, $this->translations);
            if (!empty($tmp)) {
                $output .= $tmp . PHP_EOL;
            }
        }

        if ($this->script->text()) {
            $output .= $this->script->render() . PHP_EOL;
        }

        $output .= '</head>' . PHP_EOL;
        $output .= $this->body;
        $output .= '</html>';

        return $output;
    }
}
