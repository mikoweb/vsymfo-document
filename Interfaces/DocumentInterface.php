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
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Interfaces
 */
interface DocumentInterface
{
    /**
     * zasoby
     * @param string $name
     * @return ResourcesInterface
     */
    public function resources($name);

    /**
     * elementy
     * @param string $name
     * @return ElementInterface
     */
    public function element($name);

    /**
     * dodaj tłumaczenia
     * @param array $strings
     */
    public function addTranslation(array $strings);

    /**
     * treść
     * @param string
     * @return string
     */
    public function body($set = null);

    /**
     * nazwa
     * @param string $set
     * @return string
     */
    public function name($set = null);

    /**
     * tytuł
     * @param string $set
     * @param integer $mode
     * @param string $separator
     * @return string
     */
    public function title($set = null, $mode = 0, $separator = '-');

    /**
     * autor
     * @param string $set
     * @return string
     */
    public function author($set = null);

    /**
     * strona autora
     * @param string $set
     * @return string
     */
    public function authorUrl($set = null);

    /**
     * data utworzenia
     * @param string $set
     * @return \DateTime
     */
    public function createdDate($set = null);

    /**
     * opis
     * @param string $set
     * @return string
     */
    public function description($set = null);

    /**
     * słowa kluczowe
     * @param string $set
     * @return string
     */
    public function keywords($set = null);

    /**
     * @return string
     */
    public function render();

    /**
     * nazwa formatu dokumentu
     * @return string
     */
    public function formatName();
}
