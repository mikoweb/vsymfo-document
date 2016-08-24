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

use vSymfo\Component\Document\Resources\Interfaces\ResourceManagerInterface;

/**
 * Document interface.
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Interfaces
 */
interface DocumentInterface
{
    /**
     * Get resources by name.
     *
     * @param string $name
     *
     * @return ResourceManagerInterface
     */
    public function resources($name);

    /**
     * Get element by name.
     *
     * @param string $name
     *
     * @return ElementInterface
     */
    public function element($name);

    /**
     * Add text translations.
     *
     * @param array $strings
     */
    public function addTranslation(array $strings);

    /**
     * Set or get body of document.
     *
     * @param string
     *
     * @return string
     */
    public function body($set = null);

    /**
     * Set or get document name.
     *
     * @param string $set
     *
     * @return string
     */
    public function name($set = null);

    /**
     * Set or get document title.
     *
     * @param string $set
     * @param integer $mode
     * @param string $separator
     *
     * @return string
     */
    public function title($set = null, $mode = 0, $separator = '-');

    /**
     * Set or get document author.
     * 
     * @param string $set
     *
     * @return string
     */
    public function author($set = null);

    /**
     * Set or get author's page URL.
     *
     * @param string $set
     *
     * @return string
     */
    public function authorUrl($set = null);

    /**
     * Set or get created date.
     *
     * @param string $set
     *
     * @return \DateTime
     */
    public function createdDate($set = null);

    /**
     * Set or get document description.
     *
     * @param string $set
     *
     * @return string
     */
    public function description($set = null);

    /**
     * Set or get document keywords.
     *
     * @param string $set
     *
     * @return string
     */
    public function keywords($set = null);

    /**
     * Render document.
     *
     * @return string
     */
    public function render();

    /**
     * Returns format name eg. html, pdf, xml or other.
     * 
     * @return string
     */
    public function formatName();
}
