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

/**
 * Dokument tekstowy
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
class TxtDocument extends DocumentAbstract
{
    /**
     * @var TxtElement
     */
    private $body = null;

    public function __construct()
    {
        $this->body = new TxtElement();
    }

    /**
     * {@inheritdoc }
     */
    public function formatName()
    {
        return "txt";
    }

    /**
     * Zasoby
     * @param string $name
     * @return null
     * @throws \Exception
     */
    public function resources($name)
    {
        throw new \Exception('Txt document does not have any resources.');
    }

    /**
     * Elementy
     * @param string $name
     * @return TxtElement
     * @throws \Exception
     */
    public function element($name)
    {
        switch ($name) {
            case 'body':
                return $this->body;
        }

        throw new \Exception('Element ' . $name . ' not found.');
    }

    /**
     * Treść dokumentu
     * @param string
     * @return TxtElement
     */
    public function body($set = null)
    {
        if (is_string($set)) {
            $this->body = new TxtElement($set);
        }

        return $this->body;
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->transText($this->body->render());
    }
}
