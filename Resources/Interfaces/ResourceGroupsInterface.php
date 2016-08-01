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

namespace vSymfo\Component\Document\Resources\Interfaces;

/**
 * Interfejs grupowania zasobów
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources_Interfaces
 */
interface ResourceGroupsInterface
{
    /**
     * Utwórz nową grupę zasobów
     * 
     * @param string $name
     * @param array $dependencies
     * 
     * @throws \InvalidArgumentException
     */
    public function addGroup($name, $dependencies = array());

    /**
     * Umieść nowy zasób w tablicy
     * 
     * @param ResourceInterface $res
     * @param string|null $group
     */
    public function addResource(ResourceInterface $res, $group = null);

    /**
     * Czyszczenie zasobów
     */
    public function clearResources();

    /**
     * Zapodaj zasoby o podanej grupie
     * 
     * @param string|null $name
     * 
     * @return array
     */
    public function get($name);

    /**
     * Tablica z danymi grup posortowana według kolejności na liście
     * z uwzględnieniem wzajemnych zależności
     * 
     * @return array
     */
    public function getAll();
}
