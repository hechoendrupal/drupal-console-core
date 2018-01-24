<?php

namespace Drupal\Console\Core\Generator;

/**
 * Class SiteAliasGenerator
 *
 * @package Drupal\Console\Generator
 */
class SiteAliasGenerator extends Generator
{
    /**
     * {@inheritdoc}
     */
    public function generate(array $parameters)
    {
        $this->renderFile(
            'sites/alias.yml.twig',
            $parameters['directory'] . '/sites/' . $parameters['name'] . '.yml',
            $parameters,
            FILE_APPEND
        );
    }
}
