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
use Drupal\Console\Core\Utils\DrupalFinder;

/**
 * Class Command
 *
 * @package Drupal\Console\Core\Command
 */
abstract class Command extends BaseCommand
{
    use CommandTrait;

    /**
     * @var DrupalFinder;
     */
    protected $drupalFinder;

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
     * Check maintenance mode.
     *
     * @return bool
     */
    public function isMaintenance()
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
  
    /**
     * Create Exception
     *
     * @return void
     * 
     */ 
    public function createException($message) {
        $this->getIo()->error($message);
        exit(1);
    }

    /**
     * @param \Drupal\Console\Core\Utils\DrupalFinder $drupalFinder
     */
    public function setDrupalFinder($drupalFinder) {
        $this->drupalFinder = $drupalFinder;
    }
}
