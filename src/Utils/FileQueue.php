<?php

/**
 * @file
 * Contains Drupal\Console\Core\Utils\ChainQueue.
 */

namespace Drupal\Console\Core\Utils;

/**
 * Class FileQueue
 * @package Drupal\Console\Core\Utils
 */
class FileQueue
{
    /**
     * @var $commands array
     */
    private $files;

    /**
     * @param $file string
     */
    public function addFile($file)
    {
        $this->files[] = $file;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }
}
