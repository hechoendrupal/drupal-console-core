<?php

/**
 * @file
 * Contains Drupal\Console\Generator\InitGenerator.
 */
namespace Drupal\Console\Generator;

/**
 * Class InitGenerator
 * @package Drupal\Console\Generator
 */
class InitGenerator extends Generator
{
    /**
     * @param string  $userHome
     * @param string  $executableName
     * @param boolean $override
     * @param string  $destination
     * @param array   $configParameters
     */
    public function generate(
        $userHome,
        $executableName,
        $override,
        $destination,
        $configParameters
    ) {
        $configParameters = array_map(
            function ($item) {
                if (is_bool($item)) {
                    return $item?"true":"false";
                }
                return $item;
            },
            $configParameters
        );

        $configFile = $userHome . 'config.yml';
        if ($destination) {
            $configFile = $destination.'config.yml';
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
