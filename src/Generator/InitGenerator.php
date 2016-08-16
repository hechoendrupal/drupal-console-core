<?php

/**
 * @file
 * Contains Drupal\Console\Generator\AutocompleteGenerator.
 */

namespace Drupal\Console\Generator;

class InitGenerator extends Generator
{
    public function __construct(
        $renderer,
        $fileQueue
    ) {
        $this->renderer = $renderer;
        $this->fileQueue = $fileQueue;
    }

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
