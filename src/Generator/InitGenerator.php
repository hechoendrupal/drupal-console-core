<?php

/**
 * @file
 * Contains Drupal\Console\Core\Generator\InitGenerator.
 */
namespace Drupal\Console\Core\Generator;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class InitGenerator
 *
 * @package Drupal\Console\Core\Generator
 */
class InitGenerator extends Generator
{
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
        $configGlobalDestination = $parameters['config_global_destination'];

        $configParameters = array_map(
            function ($item) {
                if (is_bool($item)) {
                    return $item ? 'true' : 'false';
                }
                return $item;
            },
            $configParameters
        );

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

        $this->renderFile(
            'core/init/config.yml.twig',
            $configFile,
            $configParameters
        );

        //Render statistics config file if this is true by the user.
        $fs = new Filesystem();
        if ($fs->exists($configGlobalDestination) || filter_var($configParameters['statistics'], FILTER_VALIDATE_BOOLEAN)) {
            $this->renderFile(
                'core/init/config.global.yml.twig',
                $configGlobalDestination,
                $configParameters
            );
        }

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
