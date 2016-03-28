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
 * Interfejs zasobów dokumentu
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources_Interfaces
 */
interface ResourceManagerInterface
{
    /**
     * @param ResourceGroupsInterface $groups
     * @param \Closure $onAdd
     */
    public function __construct(ResourceGroupsInterface $groups, \Closure $onAdd = null);

    /**
     * Zapodaj obiekt grupowania
     *
     * @return ResourceGroupsInterface
     */
    public function getGroups();

    /**
     * Dodaj zasób
     *
     * @param ResourceInterface $res
     * @param string|null $group
     */
    public function add(ResourceInterface $res, $group = null);

    /**
     * Zwraca tablice obiektów SingleResource
     *
     * @return array
     */
    public function resources();

    /**
     * Liczba zasobów
     *
     * @return integer
     */
    public function length();

    /**
     * Zapodaj kod źródłowy w wybranym formacie
     *
     * @param string $format
     * @param integer|string $group
     *
     * @return mixed
     */
    public function render($format, $group = 0);

    /**
     * Utwórz funkcję wspomagającą dodawanie zasobów o podanej nazwie
     *
     * @param string $name
     * @param \Closure $onAdd
     * @return void
     */
    public function setOnAdd($name, \Closure $onAdd);

    /**
     * Wybierz funkcje wspomagającą
     * jeśli name == null, to zostanie wyłączone
     *
     * @param $name
     * @return void
     */
    public function chooseOnAdd($name);

    /**
     * Lista nazw zarejestrowanych funkcji wspomagających
     * 
     * @return array
     */
    public function getOnAddNames();
}
