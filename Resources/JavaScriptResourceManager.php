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
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class JavaScriptResourceManager extends CombineResourceManagerAbstract
{
    const FORMAT_HTML = 'html';
    const FORMAT_JSON = 'json';
    const FORMAT_ARRAY = 'array';

    static protected $supportedFormats = [
        self::FORMAT_HTML,
        self::FORMAT_JSON,
        self::FORMAT_ARRAY
    ];

    /**
     * {@inheritdoc}
     */
    public function getSupportedFormats()
    {
        return self::$supportedFormats;
    }

    /**
     * Dodaj zasób
     * 
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
     * Zapodaj kod źródłowy w wybranym formacie
     * 
     * @param string $format
     * @param integer|string $group
     * 
     * @return string|array
     */
    public function render($format, $group = 0)
    {
        switch ($format) {
            case 'json':
                return $this->json();
            case 'array':
                return $this->formatArray();
            default:
                $output = '';
                return $this->callRender($format, $group, $output);
        }
    }

    /**
     * Format tablicowy
     * 
     * @return array
     */
    private function formatArray()
    {
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

        return $arr;
    }

    /**
     * Kod źródłowy w formacie JSON
     * 
     * @return string
     */
    private function json() {
        return json_encode($this->formatArray());
    }

    /**
     * Kod źródłowy w formacie HTML
     * 
     * @param JavaScriptResource $res
     * @param string $output
     */
    protected function render_html(JavaScriptResource $res, &$output)
    {
        foreach ($res->getUrl() as $url) {
            $tag = new HtmlElement('script');
            $tag->attr('src', $url);
            $tag->attr('type', 'text/javascript');
            $output .= $tag->render();
            $tag->destroy($tag);
        }
    }
}
