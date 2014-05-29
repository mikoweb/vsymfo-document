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

use vSymfo\Component\Document\Resources\JavaScriptResourceManager;
use vSymfo\Component\Document\Resources\JavaScriptResource;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Loader zasobów JavaScript
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_FileLoader
 */
class JavaScriptResourcesLoader extends ResourcesLoaderAbstract
{
    /**
     * @param string $filename
     * @param null|string $type
     * @throws \Exception
     */
    protected function process($filename, $type = null)
    {
        if (!($this->options['resources'] instanceof JavaScriptResourceManager)) {
            throw new \Exception('resource manager does not match JavaScript');
        }

        $config = &self::$yaml[$filename];
        $accessor = PropertyAccess::createPropertyAccessor();

        // wstawianie grup
        $groups = $this->options['resources']->getGroups();
        $insert = $accessor->getValue($config, "[resources][javascript][groups]");
        if (is_array($insert)) {
            foreach ($insert as &$group) {
                $groups->addGroup($group['name'], $group['required']);
            }
        }

        // wstawianie zasobów
        $insert = $accessor->getValue($config, "[resources][javascript][sources][$type]");
        if (is_array($insert)) {
            foreach ($insert as &$file) {
                $file = $this->options['baseurl'] . '/' . $file;
            }

            $this->options['resources']->add(
                new JavaScriptResource($this->options['name'],
                    $insert, array(
                        'combine' => $this->options['combine'],
                        'async' => $this->options['async']
                    )
                ), $type
            );
        }
    }
}
