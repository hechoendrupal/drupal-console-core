<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Debug\SettingsCommand.
 */

namespace Drupal\Console\Core\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\NestedArray;
use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Class SettingsCommand
 *
 * @package Drupal\Console\Core\Command\Settings
 */
class SettingsCommand extends Command
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var NestedArray
     */
    protected $nestedArray;

    /**
     * CheckCommand constructor.
     *
     * @param ConfigurationManager $configurationManager
     * @param NestedArray          $nestedArray
     */
    public function __construct(
        ConfigurationManager $configurationManager,
        NestedArray $nestedArray
    ) {
        $this->configurationManager = $configurationManager;
        $this->nestedArray = $nestedArray;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:settings')
            ->setDescription($this->trans('commands.debug.settings.description'))
            ->setAliases(['dse']);
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $configuration = $this->configurationManager->getConfiguration();
        $configApplication['application'] = $configuration->getRaw('application');

        $io->write(Yaml::dump($configApplication, 6, 2));

        $io->newLine();
        $io->info($this->trans('commands.debug.settings.messages.config-file'));

        $configurationFiles = [];
        foreach ($this->configurationManager->getConfigurationFiles() as $key => $configurationFile) {
            $configurationFiles = array_merge(
                $configurationFiles,
                $configurationFile
            );
        }
        $io->listing($configurationFiles);

        return 0;
    }
}
