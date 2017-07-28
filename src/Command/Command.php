<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Command.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Core\Command\Shared\CommandTrait;

/**
 * Class Command
 * @package Drupal\Console\Core\Command
 */
abstract class Command extends BaseCommand
{
    use CommandTrait;
}
