<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\ContainerAwareCommand.
 */

namespace Drupal\Console\Core\Command;

use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;

/**
 * Class ContainerAwareCommand
 *
 * @package Drupal\Console\Core\Command
 */
abstract class ContainerAwareCommand extends Command
{
    use ContainerAwareCommandTrait;
}
