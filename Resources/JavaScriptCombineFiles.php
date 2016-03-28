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

use JShrink\Minifier;
use vSymfo\Core\File\CombineFilesAbstract;

/**
 * Złączanie plików JavaScript
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class JavaScriptCombineFiles extends CombineFilesAbstract
{
    /**
     * Rozszerzenie pliku wyjściowego
     * 
     * @var string
     */
    protected $outputExtension = 'js';

    /**
     * Przetwórz zawartość pojedynczego pliku
     * 
     * @param string $content
     * 
     * @return string
     */
    protected function processOneFile(&$content)
    {
        return $content;
    }

    /**
     * Przetwórz zawartość złączonych plików
     * 
     * @param string $content
     * 
     * @return string
     */
    protected function processFiles(&$content)
    {
        return Minifier::minify($content);
    }

    /**
     * Pobieranie zawartości pliku
     * 
     * @param string $path
     * @param array $cacheFiles
     * 
     * @return string
     */
    protected function fileGetContents($path, array &$cacheFiles)
    {
        return (string)@file_get_contents($path);
    }
}
