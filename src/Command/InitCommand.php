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
use Drupal\Console\Utils\ConfigurationManager;
use Drupal\Console\Generator\InitGenerator;
use Drupal\Console\Utils\ShowFile;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class InitCommand
 * @package Drupal\Console\Command
 */
class InitCommand extends Command
{
    use CommandTrait;

    /**
     * @var ShowFile
     */
    protected $showFile;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var InitGenerator
     */
    protected $generator;

    private $configParameters = [
        'language' => 'en',
        'temp' => '/tmp',
        'learning' => false,
        'generate_inline' => false,
        'generate_chain' => false
    ];

    private $webRootDirectories = [
        'web',
        'docroot'
    ];

    /**
     * InitCommand constructor.
     * @param ShowFile             $showFile
     * @param ConfigurationManager $configurationManager
     * @param InitGenerator        $generator
     */
    public function __construct(
        ShowFile $showFile,
        ConfigurationManager $configurationManager,
        InitGenerator $generator
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
            )
            ->addOption(
                'local',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.init.options.local')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $local = $input->getOption('local');
        $configuration = $this->configurationManager->getConfiguration();

        if (!$local) {
            $local = $io->confirm(
                $this->trans('commands.init.questions.local'),
                true
            );
            $input->setOption('local', $local);
        }

        if ($local) {
            $root = null;
            foreach ($this->webRootDirectories as $webRootDirectory) {
                if (!$root && is_dir(getcwd().'/'.$webRootDirectory)) {
                    $root = $webRootDirectory;
                }
            }
            $this->configParameters['root'] = $root?:$io->askEmpty(
                $this->trans('commands.init.questions.root')
            );
        }

        $this->configParameters['language'] = $io->choiceNoList(
            $this->trans('commands.init.questions.language'),
            array_keys($configuration->get('application.languages'))
        );

        $this->configParameters['temp'] = $io->ask(
            $this->trans('commands.init.questions.temp'),
            '/tmp'
        );

        $this->configParameters['learning'] = $io->confirm(
            $this->trans('commands.init.questions.learning'),
            true
        );

        $this->configParameters['generate_inline'] = $io->confirm(
            $this->trans('commands.init.questions.generate-inline'),
            false
        );

        $this->configParameters['generate_chain'] = $io->confirm(
            $this->trans('commands.init.questions.generate-chain'),
            false
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $copiedFiles = [];
        $override = $input->getOption('override');
        $local = $input->getOption('local');

        $finder = new Finder();
        $finder->in(
            sprintf(
                '%s%s/config/dist/',
                $this->configurationManager->getApplicationDirectory(),
                DRUPAL_CONSOLE_CORE
            )
        );
        $finder->files();

        foreach ($finder as $configFile) {
            $source = sprintf(
                '%s%s/config/dist/%s',
                $this->configurationManager->getApplicationDirectory(),
                DRUPAL_CONSOLE_CORE,
                $configFile->getRelativePathname()
            );

            $destination = sprintf(
                '%s%s',
                $this->configurationManager->getConsoleDirectory(),
                $configFile->getRelativePathname()
            );

            if ($this->copyFile($source, $destination, $override)) {
                $copiedFiles[] = $destination;
            }
        }

        if ($copiedFiles) {
            $this->showFile->copiedFiles($io, $copiedFiles, false);
            $io->newLine();
        }

        $processBuilder = new ProcessBuilder(array('bash'));
        $process = $processBuilder->getProcess();
        $process->setCommandLine('echo $_');
        $process->run();
        $fullPathExecutable = explode('/', $process->getOutput());
        $executableName = trim(end($fullPathExecutable));
        $process->stop();

        $this->generator->generate(
            $this->configurationManager->getConsoleDirectory(),
            $executableName,
            $override,
            $local,
            $this->configParameters
        );

        $io->writeln($this->trans('application.messages.autocomplete'));
    }

    /**
     * @param string $source
     * @param string $destination
     * @param string $override
     * @return bool
     */
    private function copyFile($source, $destination, $override)
    {
        if (file_exists($destination)) {
            if ($override) {
                copy(
                    $destination,
                    $destination . '.old'
                );
            } else {
                return false;
            }
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
}
