<?php

/**
 * @file
 * Contains \Drupal\Console\Core\CompleteCommand.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompleteCommand extends Command
{
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
