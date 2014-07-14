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

/**
 * Złączone zasoby dokumentu
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document
 */
abstract class CombineResourceManagerAbstract extends ResourceManagerAbstract
{
    /**
     * Lista użytych nazw
     * @var array
     */
    protected $registerNames = array();

    /**
     * Dodaj zasób
     * @param ResourceInterface $res
     * @param string|null $group
     */
    public function add(ResourceInterface $res, $group = null)
    {
        parent::add($res, $group);

        // zaktualizuje listę plików do scalenia
        if ($res->getCombineObject()) {
            foreach ($res->getSources() as $source) {
                $res->getCombineObject()->addSource($source);
            }
        }

        $name = $res->getName();
        // jeśli zasób nie ma nazwy
        if (empty($name)) $res->setName((string)$group);
        // w przeciwnym razie dadaj prefix
        else $res->setName((string)$group . '_' . $name);

        // jeśli nazwa jest zajęta ustaw strategię na auto
        if (isset($this->registerNames[$res->getName()])) {
            if ($res->getCombineObject())
                $res->getCombineObject()->setOutputStrategy('auto');
        } else {
            // zarejestruj nawę
            $this->registerNames[$res->getName()] = true;
        }
    }
}
