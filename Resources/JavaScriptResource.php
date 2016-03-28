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

use vSymfo\Component\Document\CombineResourceAbstract;
use vSymfo\Core\File\Interfaces\CombineFilesInterface;

/**
 * Pojedynczy zasób JavaScript
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class JavaScriptResource extends CombineResourceAbstract
{
    /**
     * Czy wczytywać listę plików źródłowych asynchronicznie?
     * 
     * @var bool
     */
    protected $async = true;

    /**
     * Domyślne opcje konstruktora
     * 
     * @var array
     */
    protected $defaults = array();

    /**
     * @param string name
     * @param array $source
     * @param array $options
     */
    public function __construct($name, array $source, array $options = array())
    {
        $options = $this->defaults($options);
        parent::__construct($name, $source, $options);
        $this->async = (bool)$options['async'];
    }

    /**
     * Domyślne opcje konstruktora
     * 
     * @param array $options
     * @return array
     */
    protected function defaults(array $options)
    {
        return array_merge(
            array(
                'combine' => false,
                'async' => true
            )
            , $options
        );
    }

    /**
     * Czy jest asynchroniczny
     * 
     * @return bool
     */
    public function isAsync()
    {
        return $this->async;
    }

    /**
     * @param CombineFilesInterface $combine
     * 
     * @throws \UnexpectedValueException
     */
    public function setCombineObject(CombineFilesInterface $combine)
    {
        if (!$combine instanceof JavaScriptCombineFiles) {
            throw new \UnexpectedValueException('combine object does not match JavaScript');
        }

        parent::setCombineObject($combine);
    }
}
