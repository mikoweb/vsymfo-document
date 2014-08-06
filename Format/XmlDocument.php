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

use vSymfo\Component\Document\Element\TxtElement;
use vSymfo\Component\Document\Element\HtmlElement;

/**
 * Dokument XML
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Type
 */
class XmlDocument extends DocumentAbstract
{
    /**
     * @var TxtElement
     */
    private $prolog = null;

    /**
     * @var string
     */
    private $body = '';

    /**
     * @var HtmlElement
     */
    protected $root = null;

    public function __construct()
    {
        $this->prolog = new TxtElement('<?xml version="1.0" encoding="UTF-8"?>');
        $this->root = new HtmlElement('root');
    }

    /**
     * {@inheritdoc }
     */
    public function formatName()
    {
        return "xml";
    }

    /**
     * elementy
     * @param string $name
     * @return HtmlElement|TxtElement
     * @throws \Exception
     */
    public function element($name)
    {
        switch ($name) {
            case 'root':
                return $this->root;
            case 'prolog':
                return $this->prolog;
        }

        throw new \Exception('Element ' . $name . ' not found.');
    }

    /**
     * Zasoby
     * @param string $name
     * @return null
     * @throws \Exception
     */
    public function resources($name)
    {
        throw new \Exception('XML document does not have any resources.');
    }

    /**
     * treść
     * @param string
     * @return string
     */
    public function body($set = null)
    {
        if (is_string($set)) {
            $this->body = $set;
        }

        return $this->body;
    }

    /**
     * @param string $name
     * @throws \InvalidArgumentException
     */
    public function renameRoot($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('name is not string');
        }

        $newnode = new HtmlElement($name);
        foreach ($this->root->getElement()->attributes as $attrNode) {
            $newnode->getElement()->setAttribute($attrNode->name, $attrNode->value);
        }

        $this->root->destroy($this->root);
        $this->root = $newnode;
    }

    /**
     * Kod samego prologu
     * @return string
     */
    protected function prologRender()
    {
        return preg_match('/^<\?xml\s.*\?>$/', trim($this->prolog->render()))
            ? trim($this->prolog->render()) . PHP_EOL
            : '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    }

    /**
     * @return string
     */
    public function render()
    {
        $output = $this->prologRender();

        preg_match('/<'.$this->root->name().'.*?>/', $this->root->render(), $result);
        $output .= isset($result[0])
            ? $result[0] . PHP_EOL
            : '<'.$this->root->name().'>' . PHP_EOL;

        $output .= $this->body;
        $output .= '</'.$this->root->name().'>';

        return $output;
    }
}
