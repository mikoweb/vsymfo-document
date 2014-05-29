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

namespace vSymfo\Component\Document\FileLoader;

use vSymfo\Component\Document\Resources\StyleSheetResourceManager;
use vSymfo\Component\Document\Resources\StyleSheetResource;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Loader zasobów StyleSheet
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_FileLoader
 */
class StyleSheetResourcesLoader extends ResourcesLoaderAbstract
{
    /**
     * @param string $filename
     * @param null|string $type
     * @throws \Exception
     */
    protected function process($filename, $type = null)
    {
        if (!($this->options['resources'] instanceof StyleSheetResourceManager)) {
            throw new \Exception('resource manager does not match StyleSheet');
        }

        $config = &self::$yaml[$filename];
        $accessor = PropertyAccess::createPropertyAccessor();

        // wstawianie grup
        $groups = $this->options['resources']->getGroups();
        $insert = $accessor->getValue($config, "[resources][stylesheet][groups]");
        if (is_array($insert)) {
            foreach ($insert as &$group) {
                $groups->addGroup($group['name'], $group['required']);
            }
        }

        // wstawianie zasobów
        $insert = $accessor->getValue($config, "[resources][stylesheet][sources][$type]");
        if (is_array($insert)) {
            foreach ($insert as &$file) {
                $file = $this->options['baseurl'] . '/' . $file;
            }

            $this->options['resources']->add(
                new StyleSheetResource($this->options['name'],
                    $insert, array('combine' => $this->options['combine'])
                ), $type
            );
        }
    }
}
