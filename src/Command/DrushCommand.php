<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Exclude\DrushCommand.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\ChainQueue;

/**
 * Class DrushCommand
 *
 * @package Drupal\Console\Core\Command
 */
class DrushCommand extends Command
{
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
     *
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandName = $input->getArgument('command-name');

        $alternative = $this->configurationManager->readDrushEquivalents($commandName);

        $this->getIo()->newLine();
        $this->getIo()->info($this->trans('commands.drush.description'));
        $this->getIo()->newLine();

        if (!$alternative) {
            $this->getIo()->error($this->trans('commands.drush.messages.not-found'));

            return 1;
        }

        $tableHeader = ['drush','drupal console'];
        if (is_array($alternative)) {
            $this->getIo()->table(
                $tableHeader,
                $alternative
            );

            return 0;
        }

        $this->getIo()->table(
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
