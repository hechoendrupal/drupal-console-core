<?php

namespace Drupal\Console\Core\Bootstrap;

use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\DrupalFinder;

interface DrupalInterface
{
    public function __construct(
        $autoload,
        DrupalFinder $drupalFinder,
        ConfigurationManager $configurationManager
    );

    public function boot();
}
