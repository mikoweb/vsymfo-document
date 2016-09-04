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
        try {
            $minify = Minifier::minify($content);
        } catch (\Exception $e) {
            $this->setException($e);
            $minify = $content;
        }

        return $minify;
    }

    /**
     * Pobieranie zawartości pliku
     *
     * @param string $path
     * @param array $cacheFiles
     * @param string|null $relativePath
     *
     * @return string
     */
    protected function fileGetContents($path, array &$cacheFiles, $relativePath = null)
    {
        $content = @file_get_contents($path);

        if ($content === false) {
            $this->setException(new \InvalidArgumentException('Not found file: ' . $path));
        }

        return (string) $content;
    }
}
