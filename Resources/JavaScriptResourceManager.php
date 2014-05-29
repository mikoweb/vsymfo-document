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
 * Zasoby JavaScript
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class JavaScriptResourceManager extends CombineResourceManagerAbstract
{
    /**
     * Dodaj zasób
     * @param ResourceInterface $res
     * @param string $group
     * @throws \UnexpectedValueException
     */
    public function add(ResourceInterface $res, $group = null)
    {
        if (!$res instanceof JavaScriptResource) {
            throw new \UnexpectedValueException('Resource is not JavaScript');
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
            case 'json':
                return $this->json();
            case 'html':
                return $this->html();
        }

        throw new \Exception('unallowed format');
    }

    /**
     * kod źródłowy w formacie JSON
     * @return string
     */
    private function json() {
        $groups = $this->groups->getAll();
        $arr = array(
            'resources' => array(),
            'dependencies' => array(),
            'unknown' => array()
        );

        // zasoby poszczególnych grup
        foreach ($groups['groups'] as &$group) {
            $arr['resources'][$group['name']] = array();
            $arr['dependencies'][$group['name']] = $group['value']['dependencies'];
            foreach ($group['value']['resources'] as &$res) {
                $url = $res->getUrl();
                if (!empty($url)) {
                    $arr['resources'][$group['name']][] = array(
                        'url' => $url,
                        'async' => $res->isAsync()
                    );
                }
            }
        }

        // nieznane zasoby
        foreach ($groups['unknown'] as $res) {
            $url = $res->getUrl();
            if (!empty($url)) {
                $arr['unknown'][] = array(
                    'url' => $url,
                    'async' => $res->isAsync()
                );
            }
        }

        return json_encode($arr);
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
                $tag = new HtmlElement('script');
                $tag->attr('src', $url);
                $tag->attr('type', 'text/javascript');
                $html .= $tag->render();
                $tag->destroy($tag);
            }
        }

        return $html;
    }
}
