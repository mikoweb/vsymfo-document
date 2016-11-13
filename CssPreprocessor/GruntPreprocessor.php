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

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_CssPreprocessor
 */
class GruntPreprocessor extends ScssPreprocessor implements PreprocessorInterface
{
    /**
     * @param array $variables
     * @param array $importDirs
     */
    public function __construct(array $variables, array $importDirs)
    {
        parent::__construct($variables, $importDirs);
    }

    /**
     * @param string $outputFileName
     * @param string $sourceFileName
     */
    public function cleanUp($outputFileName, $sourceFileName)
    {
        if (file_exists($sourceFileName)) {
            unlink($sourceFileName);
        }

        if (file_exists($outputFileName)) {
            unlink($outputFileName);
        }

        if (file_exists($outputFileName . '.map')) {
            unlink($outputFileName . '.map');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function compile($path, $relativePath)
    {
        $pathInfo = pathinfo($path);

        if (!file_exists($pathInfo['dirname'] . '/node_modules')) {
            $process = new Process("cd $pathInfo[dirname] && npm install");
            try {
                $process->mustRun();
            } catch (ProcessFailedException $e) {
                if ($process->getExitCode() === 127) {
                    return parent::compile($path, $relativePath);
                } else {
                    throw $e;
                }
            }
        }

        $uniqId = uniqid($pathInfo['filename']);
        $sourceFileName = $pathInfo['dirname'] . '/' . $uniqId . '.scss';
        $outputFileName = $pathInfo['dirname'] . '/' . $uniqId . '.css';
        $loadPath = str_replace(['\\', '//'], ['/', '/'], json_encode($this->importDirs, JSON_UNESCAPED_SLASHES));

        $content = $this->overrideVariables();
        $content .= '@import "' . $relativePath . '";' . "\n";
        file_put_contents($sourceFileName, $content);

        $processOptions = '{"src": "' . str_replace('\\', '/', $sourceFileName) . '", "output": "' . str_replace('\\', '/', $outputFileName) . '", "loadPath": ' . $loadPath . '}';
        $processOptions = str_replace('"', '\"', $processOptions);
        $process = new Process("cd $pathInfo[dirname] && grunt vsymfo-scss -options=\"$processOptions\"");

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            if ($process->getExitCode() === 127) {
                $this->cleanUp($outputFileName, $sourceFileName);
                return parent::compile($path, $relativePath);
            } else if ($process->getExitCode() > 0 && $process->getExitCode() < 7) {
                /** Grunt error @link http://gruntjs.com/api/exit-codes */
                $this->cleanUp($outputFileName, $sourceFileName);
                throw $e;
            } else if (file_exists($outputFileName)) {
                $content = file_get_contents($outputFileName);
                $this->cleanUp($outputFileName, $sourceFileName);
                $this->parsedFiles = false;
                return $content;
            } else {
                $this->cleanUp($outputFileName, $sourceFileName);
                throw $e;
            }
        }

        $mapFileName = $outputFileName . '.map';
        if (!file_exists($mapFileName)) {
            throw new \RuntimeException('Not found map of ' . $pathInfo['basename']);
        }

        $map = json_decode(file_get_contents($mapFileName));
        $parsedFiles = array($path);
        foreach ($map->sources as $source) {
            $parsedFiles[] = str_replace('file://', '', $source);
        }
        $this->parsedFiles = $parsedFiles;

        $code = $this->removeMapEntry(file_get_contents($outputFileName));
        $this->cleanUp($outputFileName, $sourceFileName);

        return $code;
    }

    /**
     * @param string $source
     *
     * @return string
     */
    protected function removeMapEntry($source)
    {
        return trim(preg_replace('/(\/\*#\s*sourceMappingURL=){1}.*(\*\/){1}/im', '', $source));
    }
}
