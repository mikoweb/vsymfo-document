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
 * Ikona strony w formacie HTML
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Element
 */
class FaviconElement implements ElementInterface
{
    /**
     * @var bool
     */
    private $enable = true;

    /**
     * @var string
     */
    private $basepath = '';

    /**
     * @var string
     */
    private $tileColor = '';

    /**
     * Szablon html faviconów
     * @var null|string
     */
    private $faviconTemplate = '';


    public function __construct()
    {
        $this->enable(true);
        $this->setBasePath('');
        $this->setTileColor('#ffffff');
        $this->faviconTemplate = '<link rel="shortcut icon" href="{{basepath}}/favicon.ico">' .  PHP_EOL
            . '<link rel="apple-touch-icon" sizes="57x57" href="{{basepath}}/apple-touch-icon-57x57.png">' .  PHP_EOL
            . '<link rel="apple-touch-icon" sizes="114x114" href="{{basepath}}/apple-touch-icon-114x114.png">' .  PHP_EOL
            . '<link rel="apple-touch-icon" sizes="72x72" href="{{basepath}}/apple-touch-icon-72x72.png">' .  PHP_EOL
            . '<link rel="apple-touch-icon" sizes="144x144" href="{{basepath}}/apple-touch-icon-144x144.png">' .  PHP_EOL
            . '<link rel="apple-touch-icon" sizes="60x60" href="{{basepath}}/apple-touch-icon-60x60.png">' .  PHP_EOL
            . '<link rel="apple-touch-icon" sizes="120x120" href="{{basepath}}/apple-touch-icon-120x120.png">' .  PHP_EOL
            . '<link rel="apple-touch-icon" sizes="76x76" href="{{basepath}}/apple-touch-icon-76x76.png">' .  PHP_EOL
            . '<link rel="apple-touch-icon" sizes="152x152" href="{{basepath}}/apple-touch-icon-152x152.png">' .  PHP_EOL
            . '<link rel="icon" type="image/png" href="{{basepath}}/favicon-196x196.png" sizes="196x196">' .  PHP_EOL
            . '<link rel="icon" type="image/png" href="{{basepath}}/favicon-160x160.png" sizes="160x160">' .  PHP_EOL
            . '<link rel="icon" type="image/png" href="{{basepath}}/favicon-96x96.png" sizes="96x96">' .  PHP_EOL
            . '<link rel="icon" type="image/png" href="{{basepath}}/favicon-16x16.png" sizes="16x16">' .  PHP_EOL
            . '<link rel="icon" type="image/png" href="{{basepath}}/favicon-32x32.png" sizes="32x32">' .  PHP_EOL
            . '<meta name="msapplication-TileColor" content="{{tileColor}}">' .  PHP_EOL
            . '<meta name="msapplication-TileImage" content="{{basepath}}/mstile-144x144.png">' .  PHP_EOL
            . '<meta name="msapplication-config" content="{{basepath}}/browserconfig.xml">';
    }

    /**
     * Aktywuj/deaktywuj
     * @param bool $enable
     */
    public function enable($enable)
    {
        $this->enable = (bool)$enable;
    }

    /**
     * Ustaw ścieżke bazową
     * @param $path
     */
    public function setBasePath($path)
    {
        $this->basepath = htmlspecialchars($path);
    }

    /**
     * Kolor kafelka dla Windows 8
     * @param $color
     */
    public function setTileColor($color)
    {
        $this->tileColor = (string) preg_replace('/[^A-Z0-9#]/i', '', $color);
    }

    /**
     * Ustaw szablon faviconów. W kodzie można używać {{basepath}} i {{tileColor}}
     * @param string $htmlCode kod html faviconów
     */
    public function setFaviconTemplate($htmlCode)
    {
        if (!is_string($htmlCode)) {
            throw new \UnexpectedValueException('$htmlCode is not string');
        }

        $this->faviconTemplate = $htmlCode;
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->enable) {
            return str_replace(array('{{basepath}}', '{{tileColor}}'), array($this->basepath, $this->tileColor), $this->faviconTemplate);
        }

        return '';
    }
}
