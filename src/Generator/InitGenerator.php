<?php

/**
 * @file
 * Contains Drupal\Console\Core\Generator\InitGenerator.
 */
namespace Drupal\Console\Core\Generator;

use Drupal\Console\Core\Utils\ConfigurationManager;

/**
 * Class InitGenerator
 *
 * @package Drupal\Console\Core\Generator
 */
class InitGenerator extends Generator
{

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * InitGenerator constructor.
     *
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(ConfigurationManager $configurationManager)
    {
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
            if ($configParameters['statistics'] || file_exists($consoleDestination)) {
                $configParameters['statistics'] = $configParameters['statistics'] ? 'true' : 'false';
                $this->renderFile(
                    'core/init/statistics.config.yml.twig',
                    $consoleDestination,
                    $configParameters
                );
            }

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
