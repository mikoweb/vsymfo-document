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
interface PreprocessorInterface
{
    /**
     * @param string $path
     * @param string $relativePath
     * 
     * @return string
     */
    public function compile($path, $relativePath);

    /**
     * @return array
     */
    public function getParsedFiles();
}
