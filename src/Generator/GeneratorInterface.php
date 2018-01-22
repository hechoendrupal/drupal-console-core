<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Generator\Generator.
 */

namespace Drupal\Console\Core\Generator;

use Drupal\Console\Core\Utils\TwigRenderer;
use Drupal\Console\Core\Utils\FileQueue;
use Drupal\Console\Core\Utils\CountCodeLines;

/**
 * Class Generator
 *
 * @package Drupal\Console\Core\GeneratorInterface
 */
interface GeneratorInterface
{
    /**
     * @param array $parameters
     * @return void
     */
    public function generate(array $parameters);
}
