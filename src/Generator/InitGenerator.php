<?php

/**
 * @file
 * Contains Drupal\Console\Generator\AutocompleteGenerator.
 */

namespace Drupal\Console\Generator;

class InitGenerator extends Generator
{
    public function generate($userHome, $executableName)
    {
        $parameters = array(
          'executable' => $executableName,
        );

        $this->renderFile(
            'autocomplete/console.rc.twig',
            $userHome.'console.rc',
            $parameters
        );

        $this->renderFile(
            'autocomplete/console.fish.twig',
            $userHome.'drupal.fish',
            $parameters
        );
    }
}
