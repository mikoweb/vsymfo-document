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

use vSymfo\Component\Document\CssPreprocessor\NodeSassPreprocessor;
use vSymfo\Component\Document\CssPreprocessor\LessPreprocessor;
use vSymfo\Component\Document\CssPreprocessor\NonePreprocessor;
use vSymfo\Component\Document\CssPreprocessor\ScssPreprocessor;
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
    protected $outputExtension;

    /**
     * Katalogi do importowania plików less
     * 
     * @var array
     */
    protected $lessImportDirs;

    /**
     * Zmienne less
     * 
     * @var array
     */
    protected $lessVariables;

    /**
     * @var array
     */
    protected $scssImportDirs;

    /**
     * @var array
     */
    protected $scssVariables;

    /**
     * @param array $sources
     */
    public function __construct(array $sources = array())
    {
        parent::__construct($sources);
        $this->outputExtension = 'css';
        $this->lessImportDirs = array();
        $this->lessVariables = array();
        $this->scssImportDirs = array();
        $this->scssVariables = array();
    }

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
     * @param string|null $relativePath
     * 
     * @return string
     */
    protected function fileGetContents($path, array &$cacheFiles, $relativePath = null)
    {
        try {
            $pathInfo = pathinfo($path);

            switch ($pathInfo['extension']) {
                case 'scss,node':
                    $preprocessor = new NodeSassPreprocessor($this->scssVariables, $this->scssImportDirs);
                    $path = substr($path, 0, -5);
                    $relativePath = substr($relativePath, 0, -5);
                    break;
                case 'scss':
                    $preprocessor = new ScssPreprocessor($this->scssVariables, $this->scssImportDirs);
                    break;
                case 'less':
                    $preprocessor = new LessPreprocessor($this->lessVariables, $this->lessImportDirs);
                    break;
                default:
                    $preprocessor = new NonePreprocessor();
                    break;
            }

            $css = $preprocessor->compile($path, $relativePath[0] === '/' ? substr($relativePath, 1) : $relativePath);

            $parsedFiles = $preprocessor->getParsedFiles();
            if ($parsedFiles === false) {
                $cacheFiles[$path] = 0;
            } elseif (is_array($parsedFiles)) {
                foreach ($parsedFiles as $file) {
                    $cacheFiles[$file] = @filemtime($file);
                }
            }

            return $css;
        } catch (\Exception $e) {
            $this->setException($e);
            $cacheFiles[$path] = 0;
            return str_replace(str_replace($relativePath, '', $path), '{SERVER_DIRECTORY}', $e->getMessage());
        }
    }

    /**
     * Ustaw katalogi do importowania plików less
     * 
     * @param array $dirs
     * 
     * @return $this
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
     * @return $this
     */
    public function setLessVariables(array $globals)
    {
        $this->lessVariables = $globals;

        return $this;
    }

    /**
     * @param array $scssImportDirs
     *
     * @return $this
     */
    public function setScssImportDirs(array $scssImportDirs)
    {
        $this->scssImportDirs = $scssImportDirs;

        return $this;
    }

    /**
     * @param array $scssVariables
     *
     * @return $this
     */
    public function setScssVariables(array $scssVariables)
    {
        $this->scssVariables = $scssVariables;

        return $this;
    }
}
