<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Debug\ChainCommand.
 */

namespace Drupal\Console\Core\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ChainDiscovery;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ChainCommand
 *
 * @package Drupal\Console\Core\Command\Debug
 */
class ChainCommand extends Command
{
    /**
     * @var ChainDiscovery
     */
    protected $chainDiscovery;

    /**
     * ChainCommand constructor.
     *
     * @param ChainDiscovery $chainDiscovery
     */
    public function __construct(
        ChainDiscovery $chainDiscovery
    ) {
        $this->chainDiscovery = $chainDiscovery;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:chain')
            ->setDescription($this->trans('commands.debug.chain.description'))
            ->setAliases(['dch']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $files = $this->chainDiscovery->getChainFiles();

        foreach ($files as $directory => $chainFiles) {
            $io->info($this->trans('commands.debug.chain.messages.directory'), false);
            $io->comment($directory);

            $tableHeader = [
                $this->trans('commands.debug.chain.messages.file')
            ];

            $tableRows = [];
            foreach ($chainFiles as $file) {
                $tableRows[] = $file;
            }

            $io->table($tableHeader, $tableRows);
        }

        return 0;
    }
}
