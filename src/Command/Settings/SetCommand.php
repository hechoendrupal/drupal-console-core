<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Settings\SetCommand.
 */

namespace Drupal\Console\Command\Settings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class SetCommand
 * @package Drupal\Console\Command\Settings
 */
class SetCommand extends Command
{
    use CommandTrait;

    protected $configurationManager;

    protected $nestedArray;

    /**
     * CheckCommand constructor.
     * @param $configurationManager
     * @param $nestedArray
     */
    public function __construct(
        $configurationManager,
        $nestedArray
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
            ->setName('settings:set')
            ->addArgument(
                'setting-name',
                InputArgument::REQUIRED,
                $this->trans('commands.settings.set.arguments.setting-name'),
                null
            )
            ->addArgument(
                'setting-value',
                InputArgument::REQUIRED,
                $this->trans('commands.settings.set.arguments.setting-value'),
                null
            )
            ->setDescription($this->trans('commands.settings.set.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $parser = new Parser();
        $dumper = new Dumper();

        $settingName = $input->getArgument('setting-name');
        $settingValue = $input->getArgument('setting-value');

        $userConfigFile = sprintf(
            '%s/.console/config.yml',
            $this->configurationManager->getHomeDirectory()
        );

        if (!file_exists($userConfigFile)) {
            $io->error(
                sprintf(
                    $this->trans('commands.settings.set.messages.missing-file'),
                    $userConfigFile
                )
            );
            return 1;
        }

        try {
            $userConfigFileParsed = $parser->parse(file_get_contents($userConfigFile));
        } catch (\Exception $e) {
            $io->error($this->trans('commands.settings.set.messages.error-parsing').': '.$e->getMessage());
            return 1;
        }

        $parents = array_merge(['application'], explode(".", $settingName));

        $this->nestedArray->setValue($userConfigFileParsed, $parents, $settingValue, true);

        try {
            $userConfigFileDump = $dumper->dump($userConfigFileParsed, 10);
        } catch (\Exception $e) {
            $io->error($this->trans('commands.settings.set.messages.error-generating').': '.$e->getMessage());

            return 1;
        }

        try {
            file_put_contents($userConfigFile, $userConfigFileDump);
        } catch (\Exception $e) {
            $io->error($this->trans('commands.settings.set.messages.error-writing').': '.$e->getMessage());

            return 1;
        }

        if ($settingName == 'language') {
            $this->getApplication()
                ->getTranslator()
                ->loadCoreLanguage(
                    $settingValue,
                    $this->configurationManager->getApplicationDirectory()
                );
        }

        $io->success(
            sprintf(
                $this->trans('commands.settings.set.messages.success'),
                $settingName,
                $settingValue
            )
        );
    }
}
