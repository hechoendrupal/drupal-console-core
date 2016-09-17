<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Exclude\DrushCommand.
 */

namespace Drupal\Console\Command\Exclude;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\ConfigurationManager;
use Drupal\Console\Utils\ChainQueue;
use Drupal\Console\Style\DrupalStyle;

class DrushCommand extends Command
{
    use CommandTrait;

    /**
     * @var  ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * DrushCommand constructor.
     * @param ConfigurationManager $configurationManager
     * @param ChainQueue           $chainQueue
     */
    public function __construct(
        ConfigurationManager $configurationManager,
        ChainQueue $chainQueue
    ) {
        $this->configurationManager = $configurationManager;
        $this->chainQueue = $chainQueue;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('drush')
            ->setDescription($this->trans('commands.drush.description'))
            ->addArgument(
                'command-name',
                InputArgument::OPTIONAL,
                $this->trans('commands.drush.arguments.command-name'),
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $commandName = $input->getArgument('command-name');

        $alternative = $this->configurationManager->readDrushEquivalents($commandName);

        $io->newLine();
        $io->info($this->trans('commands.drush.description'));
        $io->newLine();

        if (!$alternative) {
            $io->error($this->trans('commands.drush.messages.not-found'));

            return 1;
        }

        $tableHeader = ['drush','drupal console'];
        if (is_array($alternative)) {
            $io->table(
                $tableHeader,
                $alternative
            );

            return 0;
        }

        $io->table(
            $tableHeader,
            [[$commandName, $alternative]]
        );

        if ($this->getApplication()->has($alternative)) {
            $this->chainQueue->addCommand(
                'help',
                ['command_name' => $alternative]
            );

            return 0;
        }

        return 0;
    }
}
