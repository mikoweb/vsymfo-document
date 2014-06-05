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

use vSymfo\Component\Document\Element\HtmlElement;
use vSymfo\Component\Document\ResourceManagerAbstract;
use vSymfo\Component\Document\Resources\Interfaces\ResourceInterface;

/**
 * Zasoby graficzne
 * Wsparcie dla atrybutu srcset i znacznika picture
 * http://scottjehl.github.io/picturefill/
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class ImageResourceManager extends ResourceManagerAbstract
{
    /**
     * Dodaj zasób
     * @param ResourceInterface $res
     * @param string $group
     * @throws \UnexpectedValueException
     */
    public function add(ResourceInterface $res, $group = null)
    {
        if (!$res instanceof ImageResource) {
            throw new \UnexpectedValueException('Resource is not image');
        }

        parent::add($res, $group);
    }

    /**
     * zapodaj kod źródłowy w wybranym formacie
     * @param string $format
     * @param integer|string $group
     * @return array
     */
    public function render($format, $group = 0)
    {
        $output = array();
        return $this->callRender($format, $group, $output);
    }

    /**
     * @param ImageResource $res
     * @param string $output
     */
    protected function render_html(ImageResource $res, &$output)
    {
        $this->render_html_picture($res, $output);
    }

    /**
     * kod znacznika <img>
     * @param ImageResource $res
     * @param string $output
     */
    protected function render_html_img(ImageResource $res, &$output)
    {
        $data = $res->imageData();
        $tag = new HtmlElement('img');
        $srcset = '';
        if (isset($data[-1])) {
            foreach ($data[-1] as &$img) {
                $srcset .= $img['url'];
                $srcset .= !empty($img['data']['srcset-w'])
                    ? (' ' . $img['data']['srcset-w'] . 'w') : '';
                $srcset .= !empty($img['data']['srcset-h'])
                    ? (' ' . $img['data']['srcset-h'] . 'h') : '';
                $srcset .= !empty($img['data']['srcset-x'])
                    ? (' ' . $img['data']['srcset-x'] . 'x') : '';
                $srcset .= ', ';
            }
        }

        if (!empty($data['sizes'])) {
            $tag->attr('sizes', $data['sizes']);
        }

        $tag->attr('srcset', substr($srcset, 0, -2));
        $tag->attr('alt', $res->getName());
        $output[] = $tag->render();
        $tag->destroy($tag);
    }

    /**
     * kod znacznika <picture>
     * @param ImageResource $res
     * @param string $output
     */
    protected function render_html_picture(ImageResource $res, &$output)
    {
        $data = $res->imageData();
        $output[] = '';
    }
}
