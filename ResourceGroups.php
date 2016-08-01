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

/**
 * Grupowanie zasobów
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
class ResourceGroups implements ResourceGroupsInterface
{
    /**
     * Grupy zasobów
     * @var array
     */
    protected $group = array();

    /**
     * Zasoby nie pasujące do żadnej grupy
     * @var array
     */
    protected $unknown = array();

    /**
     * Utwórz nową grupę zasobów
     * 
     * @param string $name
     * @param array $dependencies
     * 
     * @throws \InvalidArgumentException
     */
    public function addGroup($name, $dependencies = array())
    {
        if (!is_string($name) || !preg_match('/^[a-zA-Z1-9_-]*$/', $name)) {
            throw new \InvalidArgumentException('invalid group name: '.(string)$name);
        }

        if (!is_array($dependencies)) {
            throw new \InvalidArgumentException('dependencies param is not array');
        }

        if (!isset($this->group[$name])) {
            $this->group[$name] = array(
                "dependencies" => $dependencies,
                "resources" => array()
            );
        }
    }

    /**
     * Umieść nowy zasób w tablicy
     * 
     * @param ResourceInterface $res
     * @param string|null $group
     */
    public function addResource(ResourceInterface $res, $group = null)
    {
        if (is_string($group) && isset($this->group[$group])) {
            $this->group[$group]['resources'][] = $res;
        } else {
            $this->unknown[] = $res;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clearResources()
    {
        $this->unknown = [];
        foreach ($this->group as &$group) {
            $group['resources'] = [];
        }
    }

    /**
     * Zapodaj zasoby o podanej grupie
     * 
     * @param string|null $name
     * 
     * @return array
     */
    public function get($name)
    {
        if (is_null($name)) {
            // zapodaj zasoby niepasujące do żadnej grupy
            return $this->unknown;
        } else {
            // jeśli grupa nie istnieje zwróci pustą tablicę
            return is_string($name) && isset($this->group[$name]) ? $this->group[$name] : array();
        }
    }

    /**
     * Tablica z danymi grup posortowana według kolejności na liście
     * z uwzględnieniem wzajemnych zależności
     * 
     * @return array
     */
    public function getAll()
    {
        $uses = $list = array();
        $groups = &$this->group;
        $helper = function ($k, $v) use(&$list, &$helper, &$groups, &$uses) {
            foreach ($v['dependencies'] as $depe)
                $helper($depe, $groups[$depe]);

            if (!in_array($k, $uses)) {
                $list[] = array(
                    'name' => $k,
                    'value' => $v
                );
                $uses[] = $k;
            }
        };

        foreach ($groups as $k=>$v)
            $helper($k, $v);

        return array(
            'groups' => &$list,
            'unknown' => $this->unknown
        );
    }
}
