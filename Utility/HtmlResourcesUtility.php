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

namespace vSymfo\Component\Document\Utility;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use vSymfo\Component\Document\CombineResourceAbstract;
use vSymfo\Component\Document\FileLoader\JavaScriptResourcesLoader;
use vSymfo\Component\Document\FileLoader\StyleSheetResourcesLoader;
use vSymfo\Component\Document\Format\HtmlDocument;
use vSymfo\Component\Document\Resources\JavaScriptCombineFiles;
use vSymfo\Component\Document\Resources\JavaScriptResource;
use vSymfo\Component\Document\Resources\StyleSheetCombineFiles;
use vSymfo\Component\Document\Resources\StyleSheetResource;
use vSymfo\Component\Document\UrlManager;
use vSymfo\Core\File\CombineFilesCacheDB;

/**
 * Klasa do wytwarzania loadera zasobów
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Utility
 */
class HtmlResourcesUtility
{
    /**
     * @var array
     */
    protected $options = null;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array('cache_dir', 'cache_refresh', 'cache_lifetime', 'web_url'
            , 'web_dir', 'web_cache_dir', 'web_cache_url', 'less_import_dirs', 'less_globasls'));

        $resolver->setDefaults(array(
            'ignoring_files'    => array(),
            'less_import_dirs'  => array(),
            'less_globasls'     => array(),
            'versioning_enable'    => false,
            'versioning_version'   => 1,
            'versioning_timestamp' => false,
            'cdn_enable'     => false,
            'cdn_javascript' => '',
            'cdn_css'        => '',
            'cache_db_dir'   => null,
        ));

        $resolver->setAllowedTypes(array(
            'ignoring_files' => 'array',
            'cache_dir'      => 'string',
            'cache_db_dir'   => array('string', 'null'),
            'cache_refresh'  => 'bool',
            'cache_lifetime' => 'integer',
            'web_dir'        => 'string',
            'web_url'        => 'string',
            'web_cache_dir'  => 'string',
            'web_cache_url'  => 'string',
            'less_import_dirs'  => 'array',
            'less_globasls'     => 'array',
            'versioning_enable'    => 'bool',
            'versioning_version'   => 'numeric',
            'versioning_timestamp' => 'bool',
            'cdn_enable'     => 'bool',
            'cdn_javascript' => 'string',
            'cdn_css'        => 'string',
        ));
    }

    /**
     * Listener wykonywany przy dodawaniu nowego zasobu
     *
     * @param HtmlDocument $doc
     * @param string $type
     * @param string $name
     *
     * @throws \UnexpectedValueException
     */
    public function createResOnAdd(HtmlDocument $doc, $type, $name)
    {
        $allowed = array('javascript', 'stylesheet');
        if (!in_array($type, $allowed)) {
            throw new \UnexpectedValueException($type . 'is unexpected');
        }

        $options = $this->options;
        $closure = function(CombineResourceAbstract $res) use($type, $options) {
            switch ($type) {
                case 'javascript':
                    $combine = new JavaScriptCombineFiles();
                    break;
                case 'stylesheet':
                    $combine = new StyleSheetCombineFiles();
                    break;
            }

            if (!empty($options['cache_db_dir'])) {
                $cacheDb = $options['cache_db_dir'] . '.db';
            } else {
                $cacheDb = $options['web_cache_dir'] . '.db';
            }

            $combine->setInputDir($options['web_dir'])
                ->setOutputDir($options['web_cache_dir'])
                ->setOutputBaseUrl($options['web_cache_url'])
                ->setOutputForceRefresh($options['cache_refresh'])
                ->setOutputLifeTime($options['cache_lifetime'])
                ->setOutputStrategy('manual')
                ->setCacheDb(CombineFilesCacheDB::openFile($cacheDb))
            ;

            if ($type == 'stylesheet') {
                $combine->setLessImportDirs($options['less_import_dirs']);
                $combine->setLessGlobasls($options['less_globasls']);
            }

            $res->setCombineObject($combine);
            $urlManager = new UrlManager();
            $urlManager->setBaseUrl($options['web_url']);
            $urlManager->setVersioning(
                $options['versioning_enable'],
                $options['versioning_version'],
                $options['versioning_timestamp']
            );

            if ($options['cdn_enable']) {
                if ($type == 'javascript') {
                    $urlManager->setDomainPath($options['cdn_javascript']);
                } elseif ($type == 'stylesheet') {
                    $urlManager->setDomainPath($options['cdn_css']);
                }
            }
            $res->setUrlManager($urlManager);
        };

        $manager = $doc->resources($type);
        switch ($type) {
            case 'javascript':
                $manager->setOnAdd($name, function(JavaScriptResource $res) use($closure) {
                    $closure($res);
                });
                break;
            case 'stylesheet':
                $manager->setOnAdd($name, function(StyleSheetResource $res) use($closure) {
                    $closure($res);
                });
                break;
        }
    }

    /**
     * Tworzenie loadera zasobów
     * 
     * @param HtmlDocument $doc
     * @param string $type
     * @param FileLocator $locator
     * @param string $baseurl
     * @param bool $combine
     * @param bool $async
     * 
     * @return JavaScriptResourcesLoader|StyleSheetResourcesLoader
     * 
     * @throws \UnexpectedValueException
     */
    public function createResourcesLoader(HtmlDocument $doc, $type, FileLocator $locator, $baseurl, $combine = true, $async = true)
    {
        $allowed = array('javascript', 'stylesheet');
        if (!in_array($type, $allowed)) {
            throw new \UnexpectedValueException($type . 'is unexpected');
        }

        $manager = $doc->resources($type);
        $loaderOptions = array(
            'baseurl'   => $baseurl,
            'combine'   => $combine,
            'async'     => $async,
            'resources' => $manager,
            'cache_dir' => $this->options['cache_dir'],
            'cache_refresh' => $this->options['cache_refresh']
        );

        switch ($type) {
            case 'javascript':
                $loader = new JavaScriptResourcesLoader($locator, $loaderOptions);
                break;
            case 'stylesheet':
                $loader = new StyleSheetResourcesLoader($locator, $loaderOptions);
                break;
        }

        return $loader;
    }
}
