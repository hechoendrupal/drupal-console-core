<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Command.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class Command
 *
 * @package Drupal\Console\Core\Command
 */
abstract class Command extends BaseCommand
{
    use CommandTrait;

    /**
     * @var DrupalStyle
     */
    private $io;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new DrupalStyle($input, $output);
    }

    /**
     * @return \Drupal\Console\Core\Style\DrupalStyle
     */
    public function getIo()
    {
        return $this->io;
    }
}
