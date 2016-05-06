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

namespace vSymfo\Component\Document\CssPreprocessor;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_CssPreprocessor
 */
class NonePreprocessor implements PreprocessorInterface
{
    /**
     * @var array
     */
    private $parsedFiles;

    public function __construct()
    {
        $this->parsedFiles = array();
    }

    /**
     * {@inheritdoc}
     */
    public function compile($path, $relativePath)
    {
        return (string)@file_get_contents($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedFiles()
    {
        return $this->parsedFiles;
    }
}
