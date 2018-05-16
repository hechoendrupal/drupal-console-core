<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\InitCommand.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Finder\Finder;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Generator\InitGenerator;
use Drupal\Console\Core\Utils\ShowFile;

/**
 * Class InitCommand
 *
 * @package Drupal\Console\Core\Command
 */
class InitCommand extends Command
{
    /**
     * @var ShowFile
     */
    protected $showFile;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var string
     */
    protected $consoleRoot;

    /**
     * @var InitGenerator
     */
    protected $generator;

    private $configParameters = [
        'language' => 'en',
        'temp' => '/tmp',
        'chain' => false,
        'sites' => false,
        'learning' => false,
        'generate_inline' => false,
        'generate_chain' => false,
        'statistics' => true
    ];

    private $directories = [
      'chain',
      'sites',
    ];

    /**
     * InitCommand constructor.
     *
     * @param ShowFile             $showFile
     * @param ConfigurationManager $configurationManager
     * @param InitGenerator        $generator
     * @param string               $appRoot
     * @param string               $consoleRoot
     */
    public function __construct(
        ShowFile $showFile,
        ConfigurationManager $configurationManager,
        InitGenerator $generator,
        $appRoot,
        $consoleRoot = null
    ) {
        $this->showFile = $showFile;
        $this->configurationManager = $configurationManager;
        $this->generator = $generator;
        $this->appRoot = $appRoot;
        $this->consoleRoot = $consoleRoot;
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
                'destination',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.init.options.destination')
            )
            ->addOption(
                'site',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.init.options.site')
            )
            ->addOption(
                'override',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.init.options.override')
            )
            ->addOption(
                'autocomplete',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.init.options.autocomplete')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $destination = $input->getOption('destination');
        $site = $input->getOption('site');
        $autocomplete = $input->getOption('autocomplete');
        $configuration = $this->configurationManager->getConfiguration();

        if ($site && $this->appRoot && $this->consoleRoot) {
            $destination = $this->consoleRoot . '/console/';
        }

        if (!$destination) {
            if ($this->appRoot && $this->consoleRoot) {
                $destination = $this->getIo()->choice(
                    $this->trans('commands.init.questions.destination'),
                    $this->configurationManager->getConfigurationDirectories()
                );
            } else {
                $destination = $this->configurationManager
                    ->getConsoleDirectory();
            }

            $input->setOption('destination', $destination);
        }

        $this->configParameters['language'] = $this->getIo()->choiceNoList(
            $this->trans('commands.init.questions.language'),
            array_keys($configuration->get('application.languages'))
        );

        $this->configParameters['temp'] = $this->getIo()->ask(
            $this->trans('commands.init.questions.temp'),
            '/tmp'
        );

        $this->configParameters['chain'] = $this->getIo()->confirm(
            $this->trans('commands.init.questions.chain'),
            false
        );

        $this->configParameters['sites'] = $this->getIo()->confirm(
            $this->trans('commands.init.questions.sites'),
            false
        );

        $this->configParameters['learning'] = $this->getIo()->confirm(
            $this->trans('commands.init.questions.learning'),
            false
        );

        $this->configParameters['generate_inline'] = $this->getIo()->confirm(
            $this->trans('commands.init.questions.generate-inline'),
            false
        );

        $this->configParameters['generate_chain'] = $this->getIo()->confirm(
            $this->trans('commands.init.questions.generate-chain'),
            false
        );

        if (!$autocomplete) {
            $autocomplete = $this->getIo()->confirm(
                $this->trans('commands.init.questions.autocomplete'),
                false
            );
            $input->setOption('autocomplete', $autocomplete);
        }

        $this->getIo()->commentBlock(
            sprintf(
                $this->trans('commands.init.messages.statistics'),
                sprintf(
                    '%sconfig.yml',
                    $this->configurationManager->getConsoleDirectory()
                )
            )
        );

        $this->configParameters['statistics'] = $this->getIo()->confirm(
            $this->trans('commands.init.questions.statistics'),
            true
        );

        if ($this->configParameters['statistics']) {
            $this->getIo()->commentBlock(
                $this->trans('commands.init.messages.statistics-disable')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $copiedFiles = [];
        $destination = $input->getOption('destination');
        $site = $input->getOption('site');
        $autocomplete = $input->getOption('autocomplete');
        $override = $input->getOption('override');

        if ($site && $this->appRoot && $this->consoleRoot) {
            $destination = $this->consoleRoot . '/console/';
        }

        if (!$destination) {
            $destination = $this->configurationManager->getConsoleDirectory();
        }

        $finder = new Finder();
        $finder->in(
            sprintf(
                '%sdist/',
                $this->configurationManager->getVendorCoreRoot()
            )
        );
        if (!$this->configParameters['chain']) {
            $finder->exclude('chain');
        }
        if (!$this->configParameters['sites']) {
            $finder->exclude('sites');
        }
        $finder->files();

        foreach ($finder as $configFile) {
            $sourceFile = sprintf(
                '%sdist/%s',
                $this->configurationManager->getVendorCoreRoot(),
                $configFile->getRelativePathname()
            );

            $destinationFile = sprintf(
                '%s%s',
                $destination,
                $configFile->getRelativePathname()
            );

            $fs = new Filesystem();
            foreach ($this->directories as $directory) {
                if (!$fs->exists($destination.$directory)) {
                    $fs->mkdir($destination.$directory);
                }
            }

            if ($this->copyFile($sourceFile, $destinationFile, $override)) {
                $copiedFiles[] = $destinationFile;
            }
        }

        if ($copiedFiles) {
            $this->showFile->copiedFiles($this->getIo(), $copiedFiles, false);
            $this->getIo()->newLine();
        }

        $executableName = null;
        if ($autocomplete) {
            $processBuilder = new ProcessBuilder(['bash']);
            $process = $processBuilder->getProcess();
            $process->setCommandLine('echo $_');
            $process->run();
            $fullPathExecutable = explode('/', $process->getOutput());
            $executableName = trim(end($fullPathExecutable));
            $process->stop();
        }

        $this->generator->generate(
            [
            'user_home' => $this->configurationManager->getConsoleDirectory(),
            'executable_name' => $executableName,
            'override' => $override,
            'destination' => $destination,
            'config_parameters' => $this->configParameters,
            ]
        );

        $this->getIo()->writeln($this->trans('application.messages.autocomplete'));

        return 0;
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
            mkdir($filePath, 0777, true);
        }

        return copy(
            $source,
            $destination
        );
    }
}
