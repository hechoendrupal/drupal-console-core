<?php

/**
 * @file
 * Contains \Drupal\Console\Core\CompleteCommand.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;

class CompleteCommand extends Command
{
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('complete')
            ->setDescription($this->trans('commands.complete.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commands = array_keys($this->getApplication()->all());
        asort($commands);
        $output->writeln($commands);

        return 0;
    }
}
