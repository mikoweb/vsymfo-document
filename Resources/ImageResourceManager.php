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

        $urls = $res->getUrl();
        $tag->attr('src', $urls[$data['src-index']]);
        $tag->attr('srcset', substr($srcset, 0, -2));
        $tag->attr('alt', htmlspecialchars($res->getName()));
        foreach ($data['attr'] as $k => $v) {
            $tag->attr(htmlspecialchars($k), htmlspecialchars($v));
        }
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
        $tmp = '';
        $source = function(array &$arr) use (&$data, &$tmp) {
            $tag = new HtmlElement('source');
            $srcset = '';
            foreach ($arr as &$img) {
                $srcset .= $img['url'];
                $srcset .= !empty($img['data']['srcset-x'])
                    ? (' ' . $img['data']['srcset-x'] . 'x') : '';
                $srcset .= ', ';
            }
            $tag->attr('srcset', substr($srcset, 0, -2));
            $index = $arr[0]['data']['media-index'];
            if (isset($data['media'][$index])) {
                $tag->attr('media', $data['media'][$index]);
            }
            $tmp .= $tag->render() . PHP_EOL;
            $tag->destroy($tag);
        };

        foreach ($data as $k=>$v) {
            if (is_integer($k) && $k > -1 && is_array($v)) {
                $source($data[$k]);
            }
        }
        if (isset($data[-1])) {
            $source($data[-1]);
        }

        $urls = $res->getUrl();
        $img = new HtmlElement('img');
        $img->attr('src', $urls[$data['src-index']]);
        $img->attr('alt', htmlspecialchars($res->getName()));

        $tag = new HtmlElement('picture');
        $tag->attr('alt', htmlspecialchars($res->getName()));
        foreach ($data['attr'] as $k => $v) {
            $tag->attr(htmlspecialchars($k), htmlspecialchars($v));
        }
        preg_match('/<picture.*?>/', $tag->render(), $result);
        $output[] = (isset($result[0])
            ? $result[0] . PHP_EOL
            : '<picture>' . PHP_EOL)
            . "<!--[if IE 9]><video style=\"display: none;\"><![endif]-->" . PHP_EOL
            . str_replace("</source>", '', $tmp)
            . "<!--[if IE 9]></video><![endif]-->" . PHP_EOL
            . $img->render() . PHP_EOL
            . '</picture>';
        $tag->destroy($tag);
        $img->destroy($img);
    }
}
