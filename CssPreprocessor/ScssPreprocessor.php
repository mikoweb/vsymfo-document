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

use Leafo\ScssPhp\Compiler;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_CssPreprocessor
 */
class ScssPreprocessor implements PreprocessorInterface
{
    /**
     * @var array
     */
    protected $parsedFiles;

    /**
     * @var array
     */
    protected $variables;

    /**
     * @var array
     */
    protected $importDirs;

    /**
     * @param array $variables
     * @param array $importDirs
     */
    public function __construct(array $variables, array $importDirs)
    {
        $this->variables = $variables;
        $this->importDirs = $importDirs;
        $this->parsedFiles = array();
    }

    /**
     * {@inheritdoc}
     */
    public function compile($path, $relativePath)
    {
        $this->parsedFiles = array();
        $scss = new Compiler();
        $scss->setFormatter('Leafo\ScssPhp\Formatter\Compressed');

        if (!empty($this->importDirs)) {
            $scss->setImportPaths($this->importDirs);
        }

        $content = $this->overrideVariables();
        $content .= '@import "' . $relativePath . '";' . "\n";
        $css = $scss->compile($content);
        $parsedFiles = array();
        foreach ($scss->getParsedFiles() as $file => $time) {
            $parsedFiles[] = $file;
        }
        $this->parsedFiles = $parsedFiles;

        return $css;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedFiles()
    {
        return $this->parsedFiles;
    }

    /**
     * @return string
     */
    protected function overrideVariables()
    {
        $code = '';

        foreach ($this->variables as $k => $v) {
            $code .= "\$$k: $v;\n";
        }

        return $code;
    }
}
