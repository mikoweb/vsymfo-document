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
            $this->setOnAdd($onAdd);
        }
    }

    /**
     * @param callable $onAdd
     * @throws \Exception
     */
    public function setOnAdd(\Closure $onAdd)
    {
        if (is_null($this->onAdd)) {
            $reflection = new \ReflectionFunction($onAdd);
            $args = $reflection->getParameters();
            if (isset($args[0]) && is_object($args[0]->getClass())
                && $args[0]->getClass()
                    ->implementsInterface('vSymfo\Component\Document\Resources\Interfaces\ResourceInterface')
            ) {
                $this->onAdd = $onAdd;
            } else {
                throw new \Exception('not allowed Closure');
            }
        } else {
            throw new \Exception('can be set only once');
        }
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
}
