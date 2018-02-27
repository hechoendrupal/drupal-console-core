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
     * @var bool
     */
    private $maintenance = false;

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

    /**
     * Get maintenance mode.
     *
     * @return bool
     */
    public function getMaintenance()
    {
        return $this->maintenance;
    }

    /**
     * Enable maintenance mode.
     *
     * @return $this
     *   Command.
     */
    public function enableMaintenance()
    {
        $this->maintenance = true;
        return $this;
    }
}
