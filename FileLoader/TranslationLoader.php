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

namespace vSymfo\Component\Document\FileLoader;

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Yaml\Yaml;
use vSymfo\Component\Document\Configuration\TranslationConfiguration;
use vSymfo\Core\FileLoaderAbstract;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * loader tłumaczeń
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_FileLoader
 */
class TranslationLoader extends FileLoaderAbstract
{
    /**
     * Domyślne opcje
     * @param OptionsResolver $resolver
     */
    protected function setDefaultOptions(OptionsResolver $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setRequired(array('document', 'trans_closure'));
        $resolver->setAllowedTypes('document', 'object');

        $that = $this;
        $resolver->setNormalizer('document', function (Options $options, $value) use($that) {
            $that->compareOptionType('document', $value
                , 'vSymfo\Component\Document\Interfaces\DocumentInterface');
            return $value;
        });
        $resolver->setNormalizer('trans_closure', function (Options $options, $value) use($that) {
            $that->compareOptionType('trans_closure', $value, '\Closure');
            return $value;
        });
    }

    /**
     * @param string $filename
     * @param ConfigCache $cache
     */
    protected function refreshCache($filename, ConfigCache $cache)
    {
        $content = Yaml::parse(file_get_contents($filename));
        $resource = new FileResource($filename);
        $processor = new Processor();
        $processor->processConfiguration(
            new TranslationConfiguration(),
            is_null($content) ? [] : $content
        );

        $this->writeCache($cache, $resource, $content);
    }

    /**
     * @param string $filename
     * @param null|string $type
     * @return void
     */
    protected function process($filename, $type = null)
    {
        $config = &self::$yaml[$filename];
        $accessor = PropertyAccess::createPropertyAccessor();
        $using = $accessor->getValue($config, "[translations][using]");
        if (is_array($using)) {
            foreach ($using as $text) {
                $this->options['document']->addTranslation(array($text => call_user_func($this->options['trans_closure'], $text)));
            }
        }
    }
}
