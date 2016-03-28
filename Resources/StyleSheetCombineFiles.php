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

use vSymfo\Core\File\CombineFilesAbstract;

/**
 * Złączanie arkuszy stylów
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class StyleSheetCombineFiles extends CombineFilesAbstract
{
    /**
     * Rozszerzenie pliku wyjściowego
     * 
     * @var string
     */
    protected $outputExtension = 'css';

    /**
     * Katalogi do importowania plików less
     * 
     * @var array
     */
    protected $lessImportDirs = array();

    /**
     * Zmienne globalne dla kompilatora
     * 
     * @var array
     */
    protected $lessGlobasls = array();

    /**
     * Przetwórz zawartość pojedynczego pliku
     * 
     * @param string $content
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
     * @return string
     */
    protected function processFiles(&$content)
    {
        return $content;
    }

    /**
     * Pobieranie zawartości pliku
     * 
     * @param string $path
     * @param array $cacheFiles
     * @return string
     */
    protected function fileGetContents($path, array &$cacheFiles)
    {
        try {
            $parser = new \Less_Parser(array(
                'compress' => true
            ));
            if (!empty($this->lessImportDirs)) {
                $parser->SetImportDirs($this->lessImportDirs);
            }
            $parser->parseFile($path);
            $parser->ModifyVars($this->lessGlobasls);
            $css = $parser->getCss();
            foreach ($parser->allParsedFiles() as $file) {
                $cacheFiles[$file] = @filemtime($file);
            }

            return $css;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Ustaw katalogi do importowania plików less
     * 
     * @param array $dirs
     * 
     * @return StyleSheetCombineFiles
     */
    public function setLessImportDirs(array $dirs)
    {
        $this->lessImportDirs = $dirs;

        return $this;
    }

    /**
     * Ustaw zmienne globalne kompilatora
     * 
     * @param array $globals
     * 
     * @return StyleSheetCombineFiles
     */
    public function setLessGlobasls(array $globals)
    {
        $this->lessGlobasls = $globals;

        return $this;
    }
}
