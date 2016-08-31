<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Settings\DebugCommand.
 */

namespace Drupal\Console\Command\Settings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\ConfigurationManager;
use Drupal\Console\Utils\NestedArray;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class DebugCommand
 * @package Drupal\Console\Command\Settings
 */
class DebugCommand extends Command
{
    use CommandTrait;

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
            ->setName('settings:debug')
            ->setDescription($this->trans('commands.settings.debug.description'));
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
            $this->trans('commands.settings.debug.messages.config-key'),
            $this->trans('commands.settings.debug.messages.config-value'),
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
                $this->trans('commands.settings.debug.messages.config-file')
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
