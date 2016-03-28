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
 * Zasób operujący na dodatkowych plikach
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources_Interfaces
 */
interface MakeResourceInterface extends ResourceInterface
{
    /**
     * Zapisz pliki
     * 
     * @param array $options
     * @return null|array
     */
    public function save(array $options = null);

    /**
     * Posprzątaj po sobie
     * 
     * @return null|array
     */
    public function cleanup();
}
