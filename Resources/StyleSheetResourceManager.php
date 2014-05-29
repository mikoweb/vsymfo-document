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

namespace vSymfo\Component\Document\Resources;

use vSymfo\Component\Document\CombineResourceManagerAbstract;
use vSymfo\Component\Document\Element\HtmlElement;
use vSymfo\Component\Document\Resources\Interfaces\ResourceInterface;

/**
 * Zasoby CSS
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class StyleSheetResourceManager extends CombineResourceManagerAbstract
{
    /**
     * Dodaj zasób
     * @param ResourceInterface $res
     * @param string $group
     * @throws \UnexpectedValueException
     */
    public function add(ResourceInterface $res, $group = null)
    {
        if (!$res instanceof StyleSheetResource) {
            throw new \UnexpectedValueException('Resource is not StyleSheet');
        }

        parent::add($res, $group);
    }

    /**
     * zapodaj kod źródłowy w wybranym formacie
     * @param string $format
     * @return string
     * @throws \Exception
     */
    public function render($format)
    {
        switch ($format) {
            case 'html':
                return $this->html();
        }

        throw new \Exception('unallowed format');
    }

    /**
     * kod źródłowy w formacie HTML
     * @return string
     */
    private function html()
    {
        $html = '';
        foreach ($this->resources() as $res) {
            foreach ($res->getUrl() as $url) {
                $tag = new HtmlElement('link');
                $tag->attr('href', $url);
                $tag->attr('rel', 'stylesheet');
                $tag->attr('type', 'text/css');
                $html .= $tag->render();
                $tag->destroy($tag);
            }
        }

        return $html;
    }
}
