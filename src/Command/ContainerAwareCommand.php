<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\ContainerAwareCommand.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;

/**
 * Class ContainerAwareCommand
 * @package Drupal\Console\Core\Command
 */
abstract class ContainerAwareCommand extends BaseCommand
{
    use ContainerAwareCommandTrait;
}
