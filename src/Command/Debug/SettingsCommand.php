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
     * CheckCommand constructor.
     *
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        ConfigurationManager $configurationManager
    ) {
        $this->configurationManager = $configurationManager;
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
        $configuration = $this->configurationManager->getConfiguration();
        $configApplication['application'] = $configuration->getRaw('application');

        unset($configApplication['application']['autowire']);
        unset($configApplication['application']['languages']);

        $this->getIo()->write(Yaml::dump($configApplication, 6, 2));
        $this->getIo()->newLine();

        return 0;
    }
}
