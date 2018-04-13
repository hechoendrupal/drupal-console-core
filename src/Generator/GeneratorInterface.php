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

    /**
     * @param array $parameters
     * @return void
     */
    public function generate(array $parameters);
}
