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
        $files = $this->chainDiscovery->getFiles();
        $filesPerDirectory = $this->chainDiscovery->getFilesPerDirectory();

        if (!$files || !$filesPerDirectory) {
            $this->getIo()->warning($this->trans('commands.debug.chain.messages.no-files'));

            return 0;
        }

        foreach ($filesPerDirectory as $directory => $fileNames) {
            $this->getIo()->info(' ' . $this->trans('commands.debug.chain.messages.directory'), false);
            $this->getIo()->comment($directory);

            $tableHeader = [
                $this->trans('commands.debug.chain.messages.file'),
                $this->trans('commands.debug.chain.messages.command')
            ];

            $tableRows = [];
            foreach ($fileNames as $file) {
                $commandName = '';
                if (array_key_exists('command', $files[$directory.$file])) {
                    $commandName = $files[$directory.$file]['command'];
                }
                $tableRows[] = [
                    'file'  => $file,
                    'command' => $commandName
                ];
            }

            $this->getIo()->table($tableHeader, $tableRows);
        }

        return 0;
    }
}
