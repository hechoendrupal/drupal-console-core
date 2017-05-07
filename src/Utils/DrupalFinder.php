<?php

/**
 * @file
 * Contains Drupal\Console\Core\Utils\DrupalFinder.
 */

namespace Drupal\Console\Core\Utils;

use DrupalFinder\DrupalFinder as DrupalFinderBase;

/**
 * Class DrupalFinder
 *
 * @package Drupal\Console\Core\Utils
 */
class DrupalFinder extends DrupalFinderBase
{
    public function locateRoot($start_path)
    {
        if (parent::locateRoot($start_path)) {
            $composerRoot = $this->getComposerRoot();
            $vendorDir = str_replace(
                $composerRoot .'/', '', $this->getVendorDir()
            );
            if (!defined("DRUPAL_CONSOLE_CORE")) {
                define(
                    "DRUPAL_CONSOLE_CORE",
                    "/{$vendorDir}/drupal/console-core/"
                );
            }
            if (!defined("DRUPAL_CONSOLE")) {
                define("DRUPAL_CONSOLE", "/{$vendorDir}/drupal/console/");
            }
            if (!defined("DRUPAL_CONSOLE_LANGUAGE")) {
                define(
                    "DRUPAL_CONSOLE_LANGUAGE",
                    "/{$vendorDir}/drupal/console-%s/translations/"
                );
            }

            return true;
        }

        return false;
    }
}
