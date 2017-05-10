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
class NodeSassPreprocessor extends ScssPreprocessor implements PreprocessorInterface
{
    const RUN_NATIVE = 'native';
    const RUN_MANUALLY = 'manually';

    private static $runMode = self::RUN_NATIVE;

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

        $uniqId = uniqid($pathInfo['filename']);
        $sourceFileName = $pathInfo['dirname'] . '/' . $uniqId . '.scss';
        $outputFileName = $pathInfo['dirname'] . '/' . $uniqId . '.css';

        $content = $this->overrideVariables();
        $content .= '@import "' . $relativePath . '";' . "\n";
        file_put_contents($sourceFileName, $content);

        if (self::getRunMode() === self::RUN_NATIVE) {
            $command = 'npm run node-sass -- --source-map true --output-style compressed';
        } else {
            $command = 'node ./node_modules/.bin/node-sass --source-map true --output-style compressed';
        }

        foreach ($this->importDirs as $dir) {
            $command .= ' --include-path "' . str_replace(['\\', '//'], ['/', '/'], $dir) . '"';
        }

        $command .= ' "' . str_replace('\\', '/', $sourceFileName) . '"';
        $command .= ' "' . str_replace('\\', '/', $outputFileName) . '"';
        $process = new Process("cd $pathInfo[dirname] && $command");

        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            if ($process->getExitCode() === 127) {
                $this->cleanUp($outputFileName, $sourceFileName);
                return parent::compile($path, $relativePath);
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
            $result = realpath($pathInfo['dirname'] . '/' . $source);

            if ($result === false) {
                throw new \RuntimeException('Not found file ' . $pathInfo['dirname'] . '/' . $source);
            }

            if (!in_array($result, $parsedFiles, true)) {
                $parsedFiles[] = $result;
            }
        }

        $this->parsedFiles = $parsedFiles;

        $code = $this->removeMapEntry(file_get_contents($outputFileName));
        $this->cleanUp($outputFileName, $sourceFileName);

        return $code;
    }

    /**
     * @return string
     */
    public static function getRunMode()
    {
        return self::$runMode;
    }

    /**
     * @param string $runMode
     */
    public static function setRunMode($runMode)
    {
        self::$runMode = $runMode;
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
