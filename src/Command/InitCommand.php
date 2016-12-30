<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\InitCommand.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Generator\InitGenerator;
use Drupal\Console\Core\Utils\ShowFile;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class InitCommand
 * @package Drupal\Console\Core\Command
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
        'learning' => false,
        'generate_inline' => false,
        'generate_chain' => false
    ];

    /**
     * InitCommand constructor.
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
        $io = new DrupalStyle($input, $output);
        $destination = $input->getOption('destination');
        $autocomplete = $input->getOption('autocomplete');
        $configuration = $this->configurationManager->getConfiguration();

        if (!$destination) {
            if ($this->appRoot && $this->consoleRoot) {
                $destination = $io->choice(
                    $this->trans('commands.init.questions.destination'),
                    $this->configurationManager->getConfigurationDirectories()
                );
            } else {
                $destination = $this->configurationManager
                    ->getConsoleDirectory();
            }

            $input->setOption('destination', $destination);
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

        if (!$autocomplete) {
            $autocomplete = $io->confirm(
                $this->trans('commands.init.questions.autocomplete'),
                false
            );
            $input->setOption('autocomplete', $autocomplete);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $copiedFiles = [];
        $destination = $input->getOption('destination');
        $autocomplete = $input->getOption('autocomplete');
        $override = $input->getOption('override');
        if (!$destination) {
            $destination = $this->configurationManager->getConsoleDirectory();
        }

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
            $sourceFile = sprintf(
                '%s%s/config/dist/%s',
                $this->configurationManager->getApplicationDirectory(),
                DRUPAL_CONSOLE_CORE,
                $configFile->getRelativePathname()
            );

            $destinationFile = sprintf(
                '%s%s',
                $destination,
                $configFile->getRelativePathname()
            );

            if ($this->copyFile($sourceFile, $destinationFile, $override)) {
                $copiedFiles[] = $destinationFile;
            }
        }

        if ($copiedFiles) {
            $this->showFile->copiedFiles($io, $copiedFiles, false);
            $io->newLine();
        }

        $executableName = null;
        if ($autocomplete) {
            $processBuilder = new ProcessBuilder(array('bash'));
            $process = $processBuilder->getProcess();
            $process->setCommandLine('echo $_');
            $process->run();
            $fullPathExecutable = explode('/', $process->getOutput());
            $executableName = trim(end($fullPathExecutable));
            $process->stop();
        }

        $this->generator->generate(
            $this->configurationManager->getConsoleDirectory(),
            $executableName,
            $override,
            $destination,
            $this->configParameters
        );

        $io->writeln($this->trans('application.messages.autocomplete'));

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
