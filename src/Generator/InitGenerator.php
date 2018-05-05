<?php

/**
 * @file
 * Contains Drupal\Console\Core\Generator\InitGenerator.
 */
namespace Drupal\Console\Core\Generator;

use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\NestedArray;

/**
 * Class InitGenerator
 *
 * @package Drupal\Console\Core\Generator
 */
class InitGenerator extends Generator
{

    /**
     * @var NestedArray
     */
    protected $nestedArray;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * InitGenerator constructor.
     *
     * @param NestedArray          $nestedArray
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(NestedArray $nestedArray, ConfigurationManager $configurationManager)
    {
        $this->nestedArray = $nestedArray;
        $this->configurationManager = $configurationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $userHome = $parameters['user_home'];
        $executableName = $parameters['executable_name'];
        $override = $parameters['override'];
        $destination = $parameters['destination'];
        $configParameters = $parameters['config_parameters'];

        $configFile = $userHome . 'config.yml';
        if ($destination) {
            $configFile = $destination . 'config.yml';
        }

        if (file_exists($configFile) && $override) {
            copy(
                $configFile,
                $configFile . '.old'
            );
        }

        // If configFile is an override, we only change the value of statistics in the global config.
        $consoleDestination = $userHome . 'config.yml';
        if ($configFile !== $consoleDestination) {
            $this->configurationManager->updateConfigGlobalParameter(
                'statistics.enabled',
                $configParameters['statistics']
            );

            unset($configParameters['statistics']);
        }

        $configParameters = array_map(
            function ($item) {
                if (is_bool($item)) {
                    return $item ? 'true' : 'false';
                }
                return $item;
            },
            $configParameters
        );

        $this->renderFile(
            'core/init/config.yml.twig',
            $configFile,
            $configParameters
        );


        if ($executableName) {
            $parameters = [
                'executable' => $executableName,
            ];

            $this->renderFile(
                'core/autocomplete/console.rc.twig',
                $userHome . 'console.rc',
                $parameters
            );

            $this->renderFile(
                'core/autocomplete/console.fish.twig',
                $userHome . 'drupal.fish',
                $parameters
            );
        }
    }
}
