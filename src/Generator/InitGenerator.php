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
     * @param boolean $local
     * @param array   $configParameters
     */
    public function generate(
        $userHome,
        $executableName,
        $override,
        $local,
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

        if ($local) {
            $this->renderFile(
                'core/init/config.yml.twig',
                getcwd().'/console/config.yml',
                $configParameters
            );
            if (array_key_exists('root', $configParameters)) {
                unset($configParameters['root']);
            }
        }

        if (file_exists($userHome . 'config.yml') && $override) {
            copy(
                $userHome . 'config.yml',
                $userHome . 'config.yml' . '.old'
            );

            $this->renderFile(
                'core/init/config.yml.twig',
                $userHome . 'config.yml',
                $configParameters
            );
        }

        $parameters = [
          'executable' => $executableName,
        ];

        $this->renderFile(
            'core/autocomplete/console.rc.twig',
            $userHome.'console.rc',
            $parameters
        );

        $this->renderFile(
            'core/autocomplete/console.fish.twig',
            $userHome.'drupal.fish',
            $parameters
        );
    }
}
