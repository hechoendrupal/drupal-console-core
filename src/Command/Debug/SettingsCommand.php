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
        $configApplication = $configuration->get('application');

        unset($configApplication['autowire']);
        unset($configApplication['languages']);
        unset($configApplication['aliases']);
        unset($configApplication['composer']);
        unset($configApplication['default']['commands']);

        $configApplicationFlatten = [];
        $keyFlatten = '';

        $this->nestedArray->yamlFlattenArray(
            $configApplication,
            $configApplicationFlatten,
            $keyFlatten
        );

        $tableHeader = [
            $this->trans('commands.debug.settings.messages.config-key'),
            $this->trans('commands.debug.settings.messages.config-value'),
        ];

        $tableRows = [];
        foreach ($configApplicationFlatten as $ymlKey => $ymlValue) {
            $tableRows[] = [
                $ymlKey,
                $ymlValue
            ];
        }

        $io->newLine();
        $io->info(
            sprintf(
                '%s :',
                $this->trans('commands.debug.settings.messages.config-file')
            ),
            false
        );

        $io->comment(
            sprintf(
                '%s/.console/config.yml',
                $this->configurationManager->getHomeDirectory()
            ),
            true
        );

        $io->newLine();

        $io->table($tableHeader, $tableRows, 'compact');

        return 0;
    }
}
