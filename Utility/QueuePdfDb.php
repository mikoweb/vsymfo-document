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

namespace vSymfo\Component\Document\Utility;

use vSymfo\Core\File\CombineFilesCacheDB;

/**
 * Baza danych z kolejką żądań wygenerowania plików PDF
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Utility
 */
class QueuePdfDb extends CombineFilesCacheDB
{
    /**
     * Nazwa tabeli
     * @var string
     */
    protected static $tableName = 'queue';

    /**
     * {@inheritdoc}
     * @return QueuePdfDb
     */
    public static function openFile($filename)
    {
        if (!isset(self::$instance[$filename])) {
            $dir = dirname($filename);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $db = new QueuePdfDb($filename);
            $db->open($filename);
            $db->exec("PRAGMA synchronous = off; PRAGMA count_changes = off; PRAGMA temp_store = MEMORY;");
            self::$instance[$filename] = $db;

            // sprawdzanie struktury tabeli
            $results = @$db->query('pragma table_info(' . static::$tableName . ');');
            $test = array(
                array(
                    'name' => 'filepath',
                    'type' => 'text'
                ),
                array(
                    'name' => 'date_added',
                    'type' => 'integer'
                ),
                array(
                    'name' => 'block',
                    'type' => 'integer'
                ),
                array(
                    'name' => 'html_file',
                    'type' => 'text'
                ),
            );
            $i = 0;
            $del = false;
            while ($row = $results->fetchArray()) {
                if (isset($test[$i])) {
                    // jeśli nazwa kolumny lub typ jest inna niż w tablicy test oznacz tabele do usunięcia
                    if ($row['name'] != $test[$i]['name'] || $row['type'] != $test[$i]['type']) {
                        $del = true;
                    }
                }
                $i++;
            }
            if ($del) {
                $db->exec('DROP TABLE IF EXISTS ' . static::$tableName . ';');
            }

            // tworzenie tabeli jeśli nie istnieje
            if (!$db->exec(
                'CREATE TABLE IF NOT EXISTS ' . static::$tableName . '
                (
                    filepath text NOT NULL UNIQUE,
                    date_added integer NOT NULL,
                    block integer NOT NULL DEFAULT 0,
                    html_file text NOT NULL,
                    PRIMARY KEY (filepath)
                );')
            ) {
                throw new \LogicException('create ' . static::$tableName . ' table failed');
            }
        }

        return self::$instance[$filename];
    }

    /**
     * @param string $filepath
     * 
     * @return array|bool
     */
    public function select($filepath)
    {
        $results = $this->query(
            "SELECT filepath, date_added, block, html_file FROM " . static::$tableName . " WHERE filepath='$filepath' LIMIT 1"
        );
        $row = $results->fetchArray();
        if (!empty($row)) {
            return $row;
        } else {
            return false;
        }
    }

    /**
     * @param string $filepath
     * @param array $cols
     * 
     * @return bool
     */
    public function insert($filepath, array $cols)
    {
        $dateAdded = isset($cols['date_added']) ? (int)$cols['date_added'] : time();
        $block = isset($cols['block']) ? (int)$cols['block'] : 0;
        $htmlFile = isset($cols['html_file']) ? $cols['html_file'] : '';
        return @$this->exec(
            "INSERT INTO " . static::$tableName . "(filepath, date_added, block, html_file) VALUES ('$filepath', $dateAdded, $block, '$htmlFile');"
        );
    }

    /**
     * @param string $filepath
     * @param array $cols
     * 
     * @return bool
     */
    public function update($filepath, array $cols)
    {
        $block = isset($cols['block']) ? (int)$cols['block'] : 0;
        return $this->exec(
            "UPDATE " . static::$tableName . " SET block=$block WHERE filepath='$filepath';"
        );
    }

    /**
     * @return array|bool
     */
    public function getQueue()
    {
        $results = $this->query(
            "SELECT filepath, date_added, block, html_file FROM " . static::$tableName . " WHERE block=0 ORDER BY date_added ASC"
        );

        $arr = array();
        while ($row = $results->fetchArray()) {
            $arr[] = $row;
        }

        if (!empty($arr)) {
            return $arr;
        } else {
            return false;
        }
    }
}
