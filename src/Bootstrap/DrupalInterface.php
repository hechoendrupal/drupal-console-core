<?php

namespace Drupal\Console\Core\Bootstrap;

use Drupal\Console\Core\Utils\DrupalFinder;

interface DrupalInterface {
    public function __construct($autoload, DrupalFinder $drupalFinder);

    public function boot();
}
