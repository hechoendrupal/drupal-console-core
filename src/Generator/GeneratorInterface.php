<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Generator\Generator.
 */

namespace Drupal\Console\Core\Generator;

/**
 * Class Generator
 *
 * @package Drupal\Console\Core\GeneratorInterface
 */
interface GeneratorInterface
{
    public function generate(array $parameters);
}
