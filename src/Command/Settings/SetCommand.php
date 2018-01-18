<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Settings\SetCommand.
 */

namespace Drupal\Console\Core\Command\Settings;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\NestedArray;

/**
 * Class SetCommand
 *
 * @package Drupal\Console\Core\Command\Settings
 */
class SetCommand extends Command
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
            ->setName('settings:set')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                $this->trans('commands.settings.set.arguments.name'),
                null
            )
            ->addArgument(
                'value',
                InputArgument::REQUIRED,
                $this->trans('commands.settings.set.arguments.value'),
                null
            )
            ->setDescription($this->trans('commands.settings.set.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parser = new Parser();
        $dumper = new Dumper();

        $settingName = $input->getArgument('name');
        $settingValue = $input->getArgument('value');

        $userConfigFile = sprintf(
            '%s/.console/config.yml',
            $this->configurationManager->getHomeDirectory()
        );

        if (!file_exists($userConfigFile)) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.settings.set.messages.missing-file'),
                    $userConfigFile
                )
            );
            return 1;
        }

        try {
            $userConfigFileParsed = $parser->parse(
                file_get_contents($userConfigFile)
            );
        } catch (\Exception $e) {
            $this->getIo()->error(
                $this->trans(
                    'commands.settings.set.messages.error-parsing'
                ) . ': ' . $e->getMessage()
            );
            return 1;
        }

        $parents = array_merge(['application'], explode(".", $settingName));

        $this->nestedArray->setValue(
            $userConfigFileParsed,
            $parents,
            $settingValue,
            true
        );

        try {
            $userConfigFileDump = $dumper->dump($userConfigFileParsed, 10);
        } catch (\Exception $e) {
            $this->getIo()->error(
                [
                    $this->trans('commands.settings.set.messages.error-generating'),
                    $e->getMessage()
                ]
            );

            return 1;
        }

        if ($settingName == 'language') {
            $this->getApplication()
                ->getTranslator()
                ->changeCoreLanguage($settingValue);

            $translatorLanguage = $this->getApplication()->getTranslator()->getLanguage();
            if ($translatorLanguage != $settingValue) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.settings.set.messages.missing-language'),
                        $settingValue
                    )
                );

                return 1;
            }
        }

        try {
            file_put_contents($userConfigFile, $userConfigFileDump);
        } catch (\Exception $e) {
            $this->getIo()->error(
                [
                    $this->trans('commands.settings.set.messages.error-writing'),
                    $e->getMessage()
                ]
            );

            return 1;
        }

        $this->getIo()->success(
            sprintf(
                $this->trans('commands.settings.set.messages.success'),
                $settingName,
                $settingValue
            )
        );

        return 0;
    }
}
