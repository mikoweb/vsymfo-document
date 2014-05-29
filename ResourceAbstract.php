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

namespace vSymfo\Component\Document;

use Purl\Url;
use vSymfo\Component\Document\Resources\Interfaces\ResourceInterface;
use vSymfo\Component\Document\Interfaces\UrlManagerInterface;

/**
 * pojedyńczy zasób
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
abstract class ResourceAbstract implements ResourceInterface
{
    /**
     * Nazwa zasobu
     * @var string
     */
    protected $name = '';

    /**
     * Lista plików źródłowych
     * @var array
     */
    protected $source = array();

    /**
     * Przechowuje listę adresów URL
     * @var array
     */
    protected $urls = null;

    /**
     * @var UrlManagerInterface
     */
    protected $urlManager = null;

    /**
     * @param string name
     * @param array $source
     * @param array $options
     */
    public function __construct($name, array $source, array $options = array())
    {
        $this->setName($name);
        $this->source = $source;
    }

    /**
     * Filtruj ignorowane źródła
     * @param array $source
     * @param array $ignoring
     * @return bool
     */
    private static function ignoringFilter(array &$source, array $ignoring)
    {
        // usuwanie ignorowanych ścieżek źródłowych
        $keys = array();
        foreach ($source as $k=>$v) {
            $url = new Url($v);
            $path = trim($url->getPath());
            if (!empty($path) && in_array($path, $ignoring))
                $keys[] = $k;
        }
        foreach ($keys as $i)
            unset($source[$i]);
    }

    /**
     * Filtrowanie źródeł
     * @param string $type
     * @param array $args
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     */
    final public function filter($type, array $args)
    {
        if (!is_string($type))
            throw new \UnexpectedValueException('filter type is not string');

        if (method_exists(get_class(), $type . 'Filter')) {
            forward_static_call_array(array(get_class(), $type . 'Filter'), array(&$this->source, $args));
        } else {
            throw new \BadMethodCallException('undefined filter type');
        }
    }

    /**
     * Zwraca nazwę zasobu
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Ustawia nazwę zasobu
     * @param string $name
     * @throws \InvalidArgumentException
     */
    public function setName($name)
    {
        if (is_string($name)) {
            $this->name = $name;
        } else {
            throw new \InvalidArgumentException('Resource name is not string');
        }
    }

    /**
     * Zwraca listę źródeł
     * @return array
     */
    public function getSources()
    {
        return $this->source;
    }

    /**
     * Podaj tablicę adresów URL do zasobów
     * @return array
     * @throws \Exception
     */
    public function getUrl()
    {
        if (!($this->urlManager instanceof UrlManagerInterface)) {
            throw new \Exception('Wrong UrlManager object. It is not compatible with interface UrlManagerInterface.');
        }

        // generuj listę tylko jeden raz
        if ($this->urls === null) {
            $this->urls = array();
            foreach ($this->source as $source) {
                $this->urls[] = $this->urlManager->url($source, true, true);
            }
        }

        return $this->urls;
    }

    /**
     * @param UrlManagerInterface $urlManager;
     */
    public function setUrlManager(UrlManagerInterface $urlManager)
    {
        $this->urlManager = $urlManager;
    }
}
