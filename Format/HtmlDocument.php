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

use vSymfo\Component\Document\Element\TxtElement;
use vSymfo\Component\Document\Element\HtmlElement;
use vSymfo\Component\Document\Element\FaviconElement;
use vSymfo\Component\Document\Resources\JavaScriptResourceManager;
use vSymfo\Component\Document\Resources\StyleSheetResourceManager;
use vSymfo\Component\Document\ResourceGroups;

/**
 * HTML Document.
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Type
 */
class HtmlDocument extends DocumentAbstract
{
    const SCRIPTS_LOCATION_TOP = 'top';
    const SCRIPTS_LOCATION_BOTTOM = 'bottom';
    const SCRIPTS_LOCATION_NONE = 'none';

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
     * @var TxtElement
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
     * @var FaviconElement
     */
    private $favicon = null;

    /**
     * @var JavaScriptResourceManager
     */
    private $javaScript = null;

    /**
     * @var StyleSheetResourceManager
     */
    private $styleSheet = null;

    /**
     * @var string
     */
    private $customHeadCode = '';

    /**
     * @var string
     */
    private $customBottomCode = '';

    /**
     * @var \Closure
     */
    private $scriptOutput = null;

    /**
     * @var string
     */
    private $scriptsLocation;

    /**
     * @var string
     */
    private $beforeStyleSheets;

    /**
     * @var string
     */
    private $afterStyleSheets;

    public function __construct()
    {
        $this->setScriptsLocation(self::SCRIPTS_LOCATION_TOP);
        $this->beforeStyleSheets = null;
        $this->afterStyleSheets = null;
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

        $this->favicon = new FaviconElement();
        $this->favicon->enable(false);

        $this->script = new TxtElement();
        $this->author = new HtmlElement('meta');
        $this->authorUrl = new HtmlElement('link');
        $this->description = new HtmlElement('meta');
        $this->keywords = new HtmlElement('meta');
        $this->javaScript = new JavaScriptResourceManager(new ResourceGroups());
        $this->styleSheet = new StyleSheetResourceManager(new ResourceGroups());
        $this->setScriptOutput(function(JavaScriptResourceManager $manager) {
            return $manager->render('html');
        });
    }

    /**
     * {@inheritdoc }
     */
    public function formatName()
    {
        return "html";
    }

    /**
     * Set scripts output.
     *
     * @param \Closure $output
     * @throws \Exception
     */
    public function setScriptOutput(\Closure $output)
    {
        $reflection = new \ReflectionFunction($output);
        $args = $reflection->getParameters();
        if (isset($args[0]) && is_object($args[0]->getClass())
            && $args[0]->getClass()->getName() == JavaScriptResourceManager::class
        ) {
            $this->scriptOutput = $output;
        } else {
            throw new \Exception('Unexpected closure');
        }
    }

    /**
     * @param string $scriptsLocation
     */
    public function setScriptsLocation($scriptsLocation)
    {
        $this->scriptsLocation = $scriptsLocation;
    }

    /**
     * {@inheritdoc}
     * 
     * @return JavaScriptResourceManager|StyleSheetResourceManager
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
     * {@inheritdoc}
     *
     * @return HtmlElement|TxtElement|FaviconElement
     *
     * @throws \Exception
     */
    public function element($name)
    {
        switch ($name) {
            case 'script':
                return $this->script;
            case 'head':
                return $this->head;
            case 'favicon':
                return $this->favicon;
            case 'viewport':
                return $this->viewport;
            case 'html':
                return $this->html;
            case 'charset':
                return $this->charset;
            case 'doctype':
                return $this->doctype;
        }

        throw new \Exception('Element ' . $name . ' not found.');
    }

    /**
     * {@inheritdoc}
     */
    public function title($set = null, $mode = self::TITLE_ONLY_TITLE, $separator = '-')
    {
        $title = parent::title($set, $mode, $separator);
        $this->title->text(htmlspecialchars($title));
        return $title;
    }

    /**
     * Insert specific element into header only if it's not empty.
     *
     * @param HtmlElement $el
     * @param string $set
     * @param \Closure $update
     */
    private function insertToHead(HtmlElement $el, $set, \Closure $update)
    {
        if (is_string($set)) {
            if (!empty($set)) {
                $el->insertTo($this->head);
            } else {
                $el->detach();
            }

            $update($el);
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function description($set = null)
    {
        $desc = parent::description($set);
        $this->insertToHead($this->description, $set,
            function(HtmlElement $el) use($desc) {
                $el->attr('name', 'description');
                $el->attr('content', strip_tags($desc));
            });

        return $desc;
    }

    /**
     * {@inheritdoc}
     */
    public function keywords($set = null)
    {
        $words = parent::keywords($set);
        $this->insertToHead($this->keywords, $set,
            function(HtmlElement $el) use($words) {
                $el->attr('name', 'keywords');
                $el->attr('content', strip_tags($words));
            });

        return $words;
    }

    /**
     * {@inheritdoc}
     */
    public function body($set = null)
    {
        if (is_string($set)) {
            $this->body = $set;
        }

        return $this->body;
    }

    /**
     * Insert custom content on head of document.
     *
     * @param string $code
     */
    public function addCustomHeadCode($code)
    {
        if (is_string($code)) {
            $this->customHeadCode .= $code;
        }
    }

    /**
     * Insert custom content on bottom of document.
     *
     * @param string $code
     */
    public function addCustomBottomCode($code)
    {
        if (is_string($code)) {
            $this->customBottomCode .= $code;
        }
        $this->beforeStyleSheets;
        $this->afterStyleSheets;
    }

    /**
     * @param string $code
     */
    public function beforeStyleSheets($code)
    {
        $this->beforeStyleSheets = $code;
    }

    /**
     * @param string $code
     */
    public function afterStyleSheets($code)
    {
        $this->afterStyleSheets = $code;
    }

    /**
     * {@inheritdoc}
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

        $favicon = $this->favicon->render();
        if (!empty($favicon)) {
            $output .= $favicon . PHP_EOL;
        }

        $output .= $this->beforeStyleSheets;

        if ($this->styleSheet->length()) {
            $output .= $this->styleSheet->render('html') . PHP_EOL;
        }

        $output .= $this->afterStyleSheets;

        $scripts = null;
        if ($this->javaScript->length()) {
            $tmp = call_user_func($this->scriptOutput, $this->javaScript, $this->translations);
            if (!empty($tmp)) {
                $scripts .= $tmp . PHP_EOL;
            }
        }

        if (!$this->script->isEmpty()) {
            $scripts .= '<script type="text/javascript">' . $this->script->render() . '</script>' . PHP_EOL;
        }

        if ($this->scriptsLocation === self::SCRIPTS_LOCATION_TOP) {
            $output .= $scripts;
        }

        //$output .= $scripts;
        $output .= $this->customHeadCode;
        $output .= '</head>' . PHP_EOL;

        $bodyEndPos = strrpos($this->body, "</body>");
        $output .= $bodyEndPos !== false ? substr($this->body, 0, $bodyEndPos) : $this->body;

        if ($this->scriptsLocation === self::SCRIPTS_LOCATION_BOTTOM) {
            $output .= $scripts;
        }

        $output .= $this->customBottomCode;

        if ($bodyEndPos !== false) {
            $output .=  '</body>';
        }

        $output .= '</html>';

        return $output;
    }
}
