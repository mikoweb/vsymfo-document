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

use vSymfo\Component\Document\Interfaces\DocumentInterface;
use Stringy\Stringy as S;

/**
 * Dokument
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
abstract class DocumentAbstract implements DocumentInterface
{
    const TITLE_ONLY_NAME = 0;
    const TITLE_ONLY_TITLE = 1;
    const TITLE_FIRST_NAME = 2;
    const TITLE_FIRST_TITLE = 3;

    /**
     * Tłumaczenia
     * @var array
     */
    protected $translations = array();

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $title = '';

    /**
     * @var string
     */
    private $author = '';

    /**
     * @var string
     */
    private $authorUrl = '';

    /**
     * @var \DateTime
     */
    private $createdDate = null;

    /**
     * @var \DateTime
     */
    private $lastModified = null;

    /**
     * @var string
     */
    private $description = '';

    /**
     * @var string
     */
    private $keywords = '';

    /**
     * Dodaj tłumaczenia
     * 
     * @param array $strings
     */
    final public function addTranslation(array $strings)
    {
        $toDel = array();
        foreach ($strings as $k => $text) {
            if (!is_string($text) || !is_string($k)) {
                $toDel[] = $k;
            }
        }

        foreach ($toDel as $k) {
            unset($strings[$k]);
        }

        $this->translations = array_merge($this->translations, $strings);
    }

    /**
     * Tłumacz tekst
     * 
     * @param string $text
     * @param string $regex
     * 
     * @return string
     */
    final protected function transText($text, $regex = '/<trans>(.*?)<\/trans>/i')
    {
        $matches = array();
        $replace = array();
        $replaceWith = array();
        preg_match_all($regex, $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (array_search($match[0], $replace) === false) {
                $replace[] = $match[0];
                $replaceWith[] = isset($this->translations[$match[1]])
                    ? $this->translations[$match[1]]
                    : $match[1];
            }
        }

        return str_replace($replace, $replaceWith, $text);
    }

    /**
     * Nazwa
     * 
     * @param string $set
     * 
     * @return string
     */
    public function name($set = null)
    {
        if (is_string($set)) {
            $this->name = S::create($set)
                ->collapseWhitespace()
            ;
        }

        return (string)$this->name;
    }

    /**
     * Tytuł
     * 
     * @param string $set
     * @param integer $mode
     * @param string $separator
     * 
     * @return string
     */
    public function title($set = null, $mode = self::TITLE_ONLY_TITLE, $separator = '-')
    {
        if (is_string($set)) {
            $title = S::create($set)
                ->collapseWhitespace()
            ;

            switch ($mode) {
                case self::TITLE_ONLY_NAME:
                    $this->title = $this->name;
                    break;
                case self::TITLE_ONLY_TITLE:
                    $this->title = $title;
                    break;
                case self::TITLE_FIRST_NAME:
                    $this->title = $this->name.' '.$separator.' '.$title;
                    break;
                case self::TITLE_FIRST_TITLE:
                    $this->title = $title.' '.$separator.' '.$this->name;
                    break;
            }
        }

        return (string)$this->title;
    }

    /**
     * Autor
     * 
     * @param string $set
     * 
     * @return string
     */
    public function author($set = null)
    {
        if (is_string($set)) {
            $this->author = S::create($set)
                ->collapseWhitespace()
            ;
        }

        return (string)$this->author;
    }

    /**
     * Strona autora
     * 
     * @param string $set
     * 
     * @return string
     */
    public function authorUrl($set = null)
    {
        if (is_string($set)) {
            $this->authorUrl = filter_var($set, FILTER_VALIDATE_URL)
                ? $set
                : '';
        }

        return $this->authorUrl;
    }

    /**
     * Data utworzenia
     * 
     * @param string $set
     * 
     * @return \DateTime
     */
    public function createdDate($set = null)
    {
        if (is_string($set) || is_null($this->createdDate)) {
            $this->createdDate = new \DateTime(is_null($set) ? '1970-01-01 00:00:00' : $set);
        }

        return $this->createdDate;
    }

    /**
     * Data modyfikacji
     * 
     * @param string $set
     * 
     * @return \DateTime
     */
    public function lastModified($set = null)
    {
        if (is_string($set) || is_null($this->lastModified)) {
            $this->lastModified = new \DateTime(is_null($set) ? '1970-01-01 00:00:00' : $set);
        }

        return $this->lastModified;
    }

    /**
     * Opis
     * 
     * @param string $set
     * 
     * @return string
     */
    public function description($set = null)
    {
        if (is_string($set)) {
            $this->description = S::create($set)
                ->collapseWhitespace()
            ;
        }

        return (string)$this->description;
    }

    /**
     * Słowa kluczowe
     * 
     * @param string $set
     * 
     * @return string
     */
    public function keywords($set = null)
    {
        if (is_string($set)) {
            $this->keywords = S::create($set)
                ->collapseWhitespace()
            ;
        }

        return (string)$this->keywords;
    }
}
