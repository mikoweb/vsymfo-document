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
 * Zwykły tekst
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Element
 */
class TxtElement implements ElementInterface
{
    /**
     * @var string
     */
    protected $text = '';

    /**
     * @param string $content
     * @throws \InvalidArgumentException
     */
    public function __construct($content = '')
    {
        if (!is_string($content)) {
            throw new \InvalidArgumentException('content is not string');
        }

        $this->text = $content;
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->text;
    }

    /**
     * manipulowanie zawartością
     * @param callable $closure
     * @throws \Exception
     */
    public function update(\Closure $closure)
    {
        $val = $closure($this->text);
        if (!is_string($val)) {
            throw new \Exception('udating value is not string');
        }

        $this->text = $val;
    }
}
