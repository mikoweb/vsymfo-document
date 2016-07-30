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
 * Resource that has files storage.
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources_Interfaces
 */
interface MakeResourceInterface extends ResourceInterface
{
    /**
     * Save images to storage.
     *
     * @param array $options
     *
     * @return null|array Data of stored files.
     */
    public function save(array $options = null);

    /**
     * Save files from storage.
     *
     * @return null|array List of removed files.
     */
    public function cleanup();
}
