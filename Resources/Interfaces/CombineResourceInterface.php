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

use vSymfo\Core\File\Interfaces\CombineFilesInterface;

/**
 * pojedyńczy złączony zasób
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources_Interfaces
 */
interface CombineResourceInterface extends ResourceInterface
{
    /**
     * Ustawia obiekt służący do złączania plików
     * @param CombineFilesInterface $combine
     * @return void
     */
    public function setCombineObject(CombineFilesInterface $combine);

    /**
     * @return false|CombineFilesInterface
     */
    public function getCombineObject();
}