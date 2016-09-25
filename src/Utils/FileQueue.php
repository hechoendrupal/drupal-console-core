<?php

/**
 * @file
 * Contains Drupal\Console\Utils\ChainQueue.
 */

namespace Drupal\Console\Utils;

/**
 * Class FileQueue
 * @package Drupal\Console\Helper
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
