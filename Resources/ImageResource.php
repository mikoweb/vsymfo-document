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

use Symfony\Component\OptionsResolver\OptionsResolver;
use vSymfo\Component\Document\Resources\Interfaces\MakeResourceInterface;
use vSymfo\Component\Document\ResourceAbstract;

/**
 * Zasób obrazka (wsparcie dla RWD)
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class ImageResource extends ResourceAbstract implements MakeResourceInterface
{
    /**
     * opcje (dostęp jak do tablicy)
     * @var OptionsResolver
     */
    protected $options;

    /**
     * @param string name
     * @param array $source
     * @param array $options
     */
    public function __construct($name, array $source, array $options = array())
    {
        parent::__construct($name, $source, $options);
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * Domyślne opcje
     * @param OptionsResolver $resolver
     */
    protected function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array('root_dir', 'output_dir'));
        $resolver->setDefaults(array(
            'images'     => array()
        ));

        $resolver->setAllowedTypes(array(
            'root_dir'   => 'string',
            'output_dir' => 'string',
            'images'     => 'array'
        ));

        $image = new OptionsResolver();
        $image->setRequired(array('width', 'height', 'format'));
        $image->setDefaults(array(
            'index' => 0,
            'jpeg_quality' => 75,
            'png_compression_level' => 7
        ));

        $image->setAllowedTypes(array(
            'index'   => 'integer',
            'width'   => 'integer',
            'height'  => 'integer',
            'format'  => 'string',
            'jpeg_quality' => 'integer',
            'png_compression_level' => 'integer'
        ));
    }


    public function save()
    {}

    public function cleanup()
    {}
}
