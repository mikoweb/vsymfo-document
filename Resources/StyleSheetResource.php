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

use vSymfo\Component\Document\CombineResourceAbstract;
use vSymfo\Core\File\Interfaces\CombineFilesInterface;

/**
 * Pojedynczy zasób CSS
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Resources
 */
class StyleSheetResource extends CombineResourceAbstract
{
    /**
     * @param CombineFilesInterface $combine
     * @throws \UnexpectedValueException
     */
    public function setCombineObject(CombineFilesInterface $combine)
    {
        if (!$combine instanceof StyleSheetCombineFiles) {
            throw new \UnexpectedValueException('combine object does not match StyleSheet');
        }

        parent::setCombineObject($combine);
    }
}
