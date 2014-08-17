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

namespace vSymfo\Component\Document\Element;

use vSymfo\Component\Document\Interfaces\ElementInterface;

/**
 * element dokumentu HTML
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Element
 */
class HtmlElement implements ElementInterface
{
    /**
     * wstaw na początek węzła
     */
    const CHILD_PREPEND = 1;

    /**
     * wstaw na koniec węzła
     */
    const CHILD_APPEND = 0;

    /**
     * @var \DOMDocument
     */
    private static $DOM = null;

    /**
     * @var \DOMElement
     */
    protected $element = null;

    /**
     * @param string $name
     * @param string $content
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $content = null)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('name is not string');
        }

        if (is_null(self::$DOM)) {
            self::$DOM = new \DOMDocument();
            self::$DOM->strictErrorChecking = false;
            self::$DOM->substituteEntities = false;
            self::$DOM->loadHTML('<!DOCTYPE html><meta charset="utf-8">');
        }

        $this->element = self::$DOM->createElement($name);
        self::$DOM->appendChild($this->element);
        if (!empty($content)) {
            $this->insertHtml($content);
        }
    }

    /**
     * niszczenie obiektu
     */
    public function destroy(HtmlElement $element)
    {
        if ($element !== $this) {
            throw new \Exception('Cannot be destroyed: $element !== $this.');
        }

        $this->element->parentNode->removeChild($this->element);
        unset($this->element);
        unset($element);
    }

    /**
     * Odłącz element od rodzica i przyłącz do roota
     */
    public function detach()
    {
        $this->element->parentNode->removeChild($this->element);
        self::$DOM->appendChild($this->element);
    }

    /**
     * kopiowanie obiektu
     */
    public function __clone()
    {
        $this->element = $this->element->cloneNode();
        self::$DOM->appendChild($this->element);
    }

    /**
     * Nazwa elementu
     * @return string
     */
    public function name()
    {
        return $this->element->tagName;
    }

    /**
     * @param $query
     * @return \DOMNodeList
     */
    public function xpath($query)
    {
        $xpath = new \DOMXPath(self::$DOM);
        return $xpath->evaluate($query, $this->element);
    }

    /**
     * @return \DOMElement
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * dodaj kod wewnątrz elementu
     * @param string $content
     * @param int $where
     * @throws \UnexpectedValueException
     * @return HtmlElement
     */
    public function insertHtml($content, $where = self::CHILD_APPEND)
    {
        if (!is_string($content)) {
            throw new \UnexpectedValueException('content is not valid code');
        }

        $code = trim($content);
        if (!empty($code)) {
            $fragment = self::$DOM->createDocumentFragment();
            libxml_use_internal_errors(true);
            $fragment->appendXML($code);
            libxml_clear_errors();

            switch ($where) {
                case self::CHILD_APPEND:
                    $this->element->appendChild($fragment);
                    break;
                case self::CHILD_PREPEND:
                    $this->element->insertBefore($fragment, $this->element->childNodes->item(0));
                    break;
            }
        }

        return $this;
    }

    /**
     * zwróć lub ustaw zawartość tekstową
     * @param null|string $text
     * @return string|HtmlElement
     * @throws \UnexpectedValueException
     */
    public function text($text = null)
    {
        if (is_null($text)) {
            return (string)$this->element->nodeValue;
        } else if (is_string($text)) {
            $this->element->nodeValue = $text;
        } else {
            throw new \UnexpectedValueException('text is not string');
        }

        return $this;
    }

    /**
     * zwróć lub ustaw atrybut
     * @param string $name
     * @param null|string $value
     * @return string|HtmlElement
     * @throws \UnexpectedValueException
     */
    public function attr($name, $value = null)
    {
        if (!is_string($name)) {
            throw new \UnexpectedValueException('attribute name is not string');
        }

        if (is_null($value)) {
            return $this->element->getAttribute($name);
        } else if (is_string($value)) {
            $this->element->setAttribute($name, $value);
        } else {
            throw new \UnexpectedValueException('attribute value is not string');
        }

        return $this;
    }

    /**
     * usuń atrybut
     * @param string $name
     * @return HtmlElement
     * @throws \UnexpectedValueException
     */
    public function removeAttr($name)
    {
        if (!is_string($name)) {
            throw new \UnexpectedValueException('attribute name is not string');
        }

        $this->element->removeAttribute($name);
        return $this;
    }

    /**
     * dodaj klasę
     * @param string $className
     * @return HtmlElement
     * @throws \UnexpectedValueException
     */
    public function addClass($className)
    {
        if (!is_string($className)) {
            throw new \UnexpectedValueException('class name is not string');
        }

        $class = preg_split('/\s+/', $this->attr('class'));
        if (array_search($className, $class) === false) {
            $this->attr('class', trim($this->attr('class')." $className"));
        }

        return $this;
    }

    /**
     * usuń klasę
     * @param string $className
     * @return HtmlElement
     * @throws \UnexpectedValueException
     */
    public function removeClass($className)
    {
        if (!is_string($className)) {
            throw new \UnexpectedValueException('class name is not string');
        }

        $class = preg_split('/\s+/', $this->attr('class'));
        $this->attr('class', implode(' ', array_filter($class, function ($value) use($className) {
                        return $value != $className;
                    })));

        return $this;
    }

    /**
     * wstaw element do tego elementu
     * @param HtmlElement $element
     * @param int $where
     * @throws \RuntimeException
     */
    public function insert(HtmlElement $element, $where = self::CHILD_APPEND)
    {
        // idiotoodporne zabezpieczenie
        if ($this->element === $element->getElement()) {
            throw new \RuntimeException('You can not insert the Element into a self.');
        }

        switch ($where) {
            case self::CHILD_APPEND:
                $this->element->appendChild($element->getElement());
                break;
            case self::CHILD_PREPEND:
                $this->element->insertBefore($element->getElement(), $this->element->childNodes->item(0));
                break;
        }
    }

    /**
     * wstaw element do...
     * @param HtmlElement $element
     * @param int $where
     * @throws \RuntimeException
     */
    public function insertTo(HtmlElement $element, $where = self::CHILD_APPEND)
    {
        // idiotoodporne zabezpieczenie
        if ($this->element === $element->getElement()) {
            throw new \RuntimeException('You can not insert the Element into a self.');
        }

        switch ($where) {
            case self::CHILD_APPEND:
                $element->getElement()->appendChild($this->element);
                break;
            case self::CHILD_PREPEND:
                $element->getElement()->insertBefore($this->element, $element->getElement()->childNodes->item(0));
                break;
        }
    }

    /**
     * Zapodaj kod HTML.
     * Jeśli argument $xmlMode jest true to kod będzie w
     * konwencji XML np zamiast <link> będzie <link />.
     * @param boolean $inside
     * @param boolean $xmlMode
     * @return string
     */
    public function render($inside = false, $xmlMode = false)
    {
        $dom = self::$DOM;
        $saveCode = function (\DOMElement $element) use($xmlMode, $dom) {
            if ($xmlMode) {
                return $dom->saveXML($element);
            }

            return $dom->saveHTML($element);
        };

        if (!$inside) {
            return $saveCode($this->element);
        } else {
            preg_match("/<".$this->element->tagName."[^>]*>(.*?)<\/".$this->element->tagName.">/s", $saveCode($this->element), $matches);
            return trim($matches[1]);
        }
    }

    /**
     * Sprawdzanie czy element jest pusty
     * @return bool
     */
    public function isEmpty()
    {
        $text = $this->text();
        return empty($text);
    }

    /**
     * drukuj obiekt
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * zapodaj kod wszystkich elemetów
     * @return string
     */
    final public static function renderAll()
    {
        return self::$DOM->saveHTML();
    }
}
