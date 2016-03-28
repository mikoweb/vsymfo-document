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

namespace vSymfo\Component\Document\Interfaces;

/**
 * Interfejs dokumentu
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Interfaces
 */
interface DocumentInterface
{
    /**
     * Zasoby
     * 
     * @param string $name
     * 
     * @return ResourcesInterface
     */
    public function resources($name);

    /**
     * Elementy
     * 
     * @param string $name
     * 
     * @return ElementInterface
     */
    public function element($name);

    /**
     * Dodaj tłumaczenia
     * 
     * @param array $strings
     */
    public function addTranslation(array $strings);

    /**
     * Treść
     * 
     * @param string
     * 
     * @return string
     */
    public function body($set = null);

    /**
     * Nazwa
     * 
     * @param string $set
     * 
     * @return string
     */
    public function name($set = null);

    /**
     * Tytuł
     * 
     * @param string $set
     * @param integer $mode
     * @param string $separator
     * 
     * @return string
     */
    public function title($set = null, $mode = 0, $separator = '-');

    /**
     * Autor
     * 
     * @param string $set
     * 
     * @return string
     */
    public function author($set = null);

    /**
     * Strona autora
     * 
     * @param string $set
     * 
     * @return string
     */
    public function authorUrl($set = null);

    /**
     * Data utworzenia
     * 
     * @param string $set
     * 
     * @return \DateTime
     */
    public function createdDate($set = null);

    /**
     * Opis
     * 
     * @param string $set
     * 
     * @return string
     */
    public function description($set = null);

    /**
     * Słowa kluczowe
     * 
     * @param string $set
     * 
     * @return string
     */
    public function keywords($set = null);

    /**
     * @return string
     */
    public function render();

    /**
     * Nazwa formatu dokumentu
     * 
     * @return string
     */
    public function formatName();
}
