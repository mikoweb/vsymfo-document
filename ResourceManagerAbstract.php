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

use vSymfo\Component\Document\Resources\Interfaces\ResourceInterface;
use vSymfo\Component\Document\Resources\Interfaces\ResourceGroupsInterface;
use vSymfo\Component\Document\Resources\Interfaces\ResourceManagerInterface;

/**
 * Zasoby dokumentu
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
abstract class ResourceManagerAbstract implements ResourceManagerInterface
{
    /**
     * grupowanie
     * @var ResourceGroupsInterface
     */
    protected $groups = null;

    /**
     * Operacje wykonywane w trakcie wstawiania zasobu
     * @var \Closure
     */
    protected $onAdd = null;

    /**
     * @var array
     */
    protected $chooseOnAdd = array();

    /**
     * liczba zasobów
     * @var int
     */
    protected $length = 0;

    /**
     * @param ResourceGroupsInterface $groups
     * @param callable $onAdd
     * @throws \Exception
     */
    public function __construct(ResourceGroupsInterface $groups, \Closure $onAdd = null)
    {
        $this->groups = $groups;
        if (!is_null($onAdd)) {
            $this->setOnAdd("default", $onAdd);
            $this->chooseOnAdd("default");
        }
    }

    /**
     * ustaw funckcję dodającą o podanej nazwie
     * @param string $name
     * @param \Closure $onAdd
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function setOnAdd($name, \Closure $onAdd)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('invalid name');
        }

        if (!isset($this->chooseOnAdd[$name])) {
            $reflection = new \ReflectionFunction($onAdd);
            $args = $reflection->getParameters();
            if (isset($args[0]) && is_object($args[0]->getClass())
                && $args[0]->getClass()
                    ->implementsInterface('vSymfo\Component\Document\Resources\Interfaces\ResourceInterface')
            ) {
                $this->chooseOnAdd[$name] = $onAdd;
            } else {
                throw new \RuntimeException('not allowed Closure');
            }
        } else {
            throw new \RuntimeException('OnAddResource closure: "' . $name . '" is registered.');
        }
    }

    /**
     * wybierz funkcje dodającą
     * @param string|null $name
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function chooseOnAdd($name)
    {
        if (is_string($name)) {
            if (isset($this->chooseOnAdd[$name])
                && $this->chooseOnAdd[$name] instanceof \Closure
            ) {
                $this->onAdd = $this->chooseOnAdd[$name];
            } else {
                throw new \RuntimeException('OnAddResource closure: "' . $name . '" not exists.');
            }
        } elseif (is_null($name)) {
            $this->onAdd = null;
        } else {
            throw new \InvalidArgumentException('invalid name');
        }
    }

    /**
     * lista nazw zarejestrowanych funkcji dodających
     * @return array
     */
    public function getOnAddNames()
    {
        $names = array();
        foreach ($this->chooseOnAdd as $k=>$v) {
            $names[] = $k;
        }

        return $names;
    }

    /**
     * Zapodaj obiekt grupowania
     * @return ResourceGroupsInterface
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Zwraca tablice obiketów SingleResource
     * @return array
     */
    public function resources()
    {
        $tmp = array();
        $arr = $this->groups->getAll();
        foreach ($arr['groups'] as &$group) {
            foreach ($group['value']['resources'] as $res) {
                $tmp[] = $res;
            }
        }

        if (!empty($arr['unknown'])) {
            foreach ($arr['unknown'] as $res) {
                $tmp[] = $res;
            }
        }

        return $tmp;
    }

    /**
     * Dodaj zasób
     * @param ResourceInterface $res
     * @param string|null $group
     */
    public function add(ResourceInterface $res, $group = null)
    {
        if ($this->onAdd instanceof \Closure) {
            call_user_func($this->onAdd, $res);
        }
        $this->groups->addResource($res, $group);
        $this->length++;
    }

    /**
     * liczba zasobów
     * @return integer
     */
    public function length()
    {
        return $this->length;
    }


    /**
     * automatyczny wybór metody formatowania
     * @param string $format
     * @param integer|string $group
     * @param mixed $output
     * @return mixed
     * @throws \Exception
     */
    final protected function callRender($format, $group, &$output)
    {
        if (!is_string($format)) {
            throw new \Exception('format is not string');
        }

        $call = "render_$format";
        if (!method_exists($this, $call)) {
            throw new \Exception('unallowed format: ' . $format);
        }

        if ($group === 0) {
            $resources = $this->resources();
        } else {
            $resources = $this->groups->get($group);
            $resources = isset($resources['resources'])
                ? $resources['resources']
                : array();
        }

        foreach ($resources as $res) {
            call_user_func_array(array($this, $call), array($res, &$output));
        }

        return $output;
    }
}
