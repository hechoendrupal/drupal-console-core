<?php

/**
 * @file
 * Contains Drupal\Console\Core\Command\CountCodeLines.
 */

namespace Drupal\Console\Core\Utils;

/**
 * Class CountCodeLines
 *
 * @package Drupal\Console\Core\Utils
 */
class CountCodeLines
{
    /**
     * @var $countCodeLine integer
     */
    private $countCodeLine;

    /**
     * @param $countCodeLine integer
     */
    public function addCountCodeLines($countCodeLine)
    {
        $this->countCodeLine = $this->countCodeLine + $countCodeLine;
    }

    /**
     * @return integer
     */
    public function getCountCodeLines()
    {
        return $this->countCodeLine;
    }
}
