<?php

/**
 * @file
 * Contains Drupal\Console\Core\Utils\ChainQueue.
 */

namespace Drupal\Console\Core\Utils;

/**
 * Class FileQueue
 *
 * @package Drupal\Console\Core\Utils
 */
class FileQueue
{
    /**
     * @var $commands array
     */
    private $files;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * FileQueue constructor.
     *
     * @param string                     $appRoot
     */
    public function __construct($appRoot)
    {
        $this->appRoot = $appRoot;
    }

    /**
     * @param $file string
     */
    public function addFile($file)
    {
        $file = str_replace($this->appRoot, '', $file);
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
