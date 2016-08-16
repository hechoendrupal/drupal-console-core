<?php

/**
 * @file
 * Contains \Drupal\Console\Command\InitCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class InitCommand
 * @package Drupal\Console\Command
 */
class InitCommand extends Command
{
    use CommandTrait;

    protected $showFile;

    protected $configurationManager;

    protected $generator;

    /**
     * InitCommand constructor.
     * @param $showFile
     * @param $configurationManager
     * @param $generator
     */
    public function __construct(
        $showFile,
        $configurationManager,
        $generator
    ) {
        $this->showFile = $showFile;
        $this->configurationManager = $configurationManager;
        $this->generator = $generator;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription($this->trans('commands.init.description'))
            ->addOption(
                'override',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.init.options.override')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $copiedFiles = [];
        $override = false;
        if ($input->hasOption('override')) {
            $override = $input->getOption('override');
        }

        $finder = new Finder();
        $finder->in(
            sprintf(
                '%s%s/config/dist/',
                DRUPAL_CONSOLE_CORE,
                $this->configurationManager->getApplicationDirectory()
            )
        );
        $finder->files();

        foreach ($finder as $configFile) {
            $source = sprintf(
                '%s%s/config/dist/%s',
                DRUPAL_CONSOLE_CORE,
                $this->configurationManager->getApplicationDirectory(),
                $configFile->getRelativePathname()
            );

            $destination = sprintf(
                '%s/%s',
                $this->getConsoleDirectory(),
                $configFile->getRelativePathname()
            );

            if ($this->copyFile($source, $destination, $override)) {
                $copiedFiles[] = $configFile->getRelativePathname();
            }
        }

        if ($copiedFiles) {
            $this->showFile->copiedFiles($io, $copiedFiles);
        }

        $this->createAutocomplete();
        $io->newLine(1);
        $io->writeln($this->trans('application.messages.autocomplete'));
    }

    protected function createAutocomplete()
    {
        $processBuilder = new ProcessBuilder(array('bash'));
        $process = $processBuilder->getProcess();
        $process->setCommandLine('echo $_');
        $process->run();
        $fullPathExecutable = explode('/', $process->getOutput());
        $executableName = trim(end($fullPathExecutable));
        $process->stop();

        $this->generator->generate(
            $this->getConsoleDirectory(),
            $executableName
        );
    }

    /**
     * @param string $source
     * @param string $destination
     * @param string $override
     * @return bool
     */
    private function copyFile($source, $destination, $override)
    {
        if (file_exists($destination) && !$override) {
            return false;
        }

        $filePath = dirname($destination);
        if (!is_dir($filePath)) {
            mkdir($filePath);
        }

        return copy(
            $source,
            $destination
        );
    }

    private function getConsoleDirectory()
    {
        return sprintf('%s/.console/', $this->configurationManager->getHomeDirectory());
    }
}
