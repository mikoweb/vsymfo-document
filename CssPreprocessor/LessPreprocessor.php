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
class LessPreprocessor implements PreprocessorInterface
{
    /**
     * @var array
     */
    private $parsedFiles;

    /**
     * @var array
     */
    private $variables;

    /**
     * @var array
     */
    private $importDirs;

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
        $parser = new \Less_Parser(array(
            'compress' => true
        ));

        if (!empty($this->importDirs)) {
            $parser->SetImportDirs($this->importDirs);
        }

        $parser->parseFile($path);
        $parser->ModifyVars($this->variables);
        $css = $parser->getCss();
        $this->parsedFiles = $parser->allParsedFiles();

        return $css;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedFiles()
    {
        return $this->parsedFiles;
    }
}
