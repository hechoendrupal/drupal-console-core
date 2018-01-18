<?php

namespace Drupal\Console\Core\Generator;

/**
 * Class SiteAliasGenerator
 *
 * @package Drupal\Console\Generator
 */
class SiteAliasGenerator extends Generator implements GeneratorInterface
{
    /**
     * @param array $parameters
     */
    public function generate($parameters = [])
    {
        $this->renderFile(
            'sites/alias.yml.twig',
            $parameters['directory'].'/sites/'.$parameters['name'].'.yml',
            $parameters,
            FILE_APPEND
        );
    }
}
