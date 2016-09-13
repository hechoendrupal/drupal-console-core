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
                'auto',
                null,
                InputOption::VALUE_NONE,
                $this->trans('commands.init.options.auto')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $configuration = $this->configurationManager->getConfiguration();
        $configApplication = $configuration->get('application');
        $copiedFiles = [];
        $override = false;
        if ($input->hasOption('override')) {
            $override = $input->getOption('override');
        }
        if ($input->hasOption('auto')) {
            $no_interaction = $input->getOption('auto');
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

            if ($configFile->getBaseName() == "config.yml"){

                $values = (!$no_interaction)?
                    $this->getUserChoices($io, $configApplication):
                    $values = $this->getDefaultUserChoices();

                //@TODO: ¿ $override option ?
                try {
                    $this->generator->generateConfig(
                    // @TODO: detect --root, @site or we are in a site
                        $this->getConsoleDirectory(), //@FIXME
                        $values
                    );
                } catch (\Exception $e) {
                    $io->error($this->trans('commands.module.init.error-config'));
                    return;
                }

            } else{

                $source = sprintf(
                    '%s%s/config/dist/%s',
                    $this->configurationManager->getApplicationDirectory(),
                    DRUPAL_CONSOLE_CORE,
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

    private function getUserChoices($io, $configApplication)
    {
        // global or site configuration
        $user_choices['globally'] = $io->confirm(
            $this->trans('commands.module.init.questions.global'),
            false
        );

        if ($user_choices['globally']) {
            $user_choices['globally'] = $this->getConsoleDirectory() . 'config.yml';
        } else {
            $user_choices['globally'] = $this->getConsoleDirectory() . 'config.yml';
        }

        // language
        $user_choices['language'] = $io->choice(
            $this->trans('commands.module.init.questions.language'),
            $configApplication['languages']
        );

        // temp
        $user_choices['temp'] = $io->ask(
            $this->trans('commands.module.init.questions.temp'),
            '/tmp'
        );

        // options.learning
        $user_choices['learning'] = $io->confirm(
            $this->trans('commands.module.init.questions.learning'),
            true
        );

        // options.learning
        $user_choices['examples'] = $io->confirm(
            $this->trans('commands.module.init.questions.examples'),
            true
        );

        // options.learning
        $user_choices['generate_inline'] = $io->confirm(
            $this->trans('commands.module.init.questions.generate-inline'),
            false
        );

        // options.learning
        $user_choices['generate_chain'] = $io->confirm(
            $this->trans('commands.module.init.questions.generate-chain'),
            false
        );

        return $user_choices;
    }


    private function getDefaultUserChoices()
    {
        $user_choices['globally'] = $this->getConsoleDirectory(); //@FIXME
        $user_choices['language'] = 'en';
        $user_choices['temp'] = '/tmp';
        $user_choices['learning'] = false;
        $user_choices['examples'] = false;
        $user_choices['generate_inline'] = false;
        $user_choices['generate_chain'] = false;

        return $user_choices;
    }
}
