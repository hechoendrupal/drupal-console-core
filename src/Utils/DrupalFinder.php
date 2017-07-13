<?php

/**
 * @file
 * Contains Drupal\Console\Core\Utils\DrupalFinder.
 */

namespace Drupal\Console\Core\Utils;

use DrupalFinder\DrupalFinder as DrupalFinderBase;
use Webmozart\PathUtil\Path;

/**
 * Class DrupalFinder
 *
 * @package Drupal\Console\Core\Utils
 */
class DrupalFinder extends DrupalFinderBase
{

    /**
     * @var string
     */
    protected $consoleCorePath;

    /**
     * @var string
     */
    protected $consolePath;

    /**
     * @var string
     */
    protected $consoleLanguagePath;

    public function locateRoot($start_path)
    {
        $vendorDir = 'vendor';
        if (parent::locateRoot($start_path)) {
            $vendorDir = Path::makeRelative(
                $this->getVendorDir(),
                $this->getComposerRoot()
            );

            $this->definePaths($vendorDir);
            $this->defineConstants($vendorDir);

            return true;
        }

        $this->definePaths($vendorDir);
        $this->defineConstants($vendorDir);

        return false;
    }

    protected function definePaths($vendorDir)
    {
        $this->consoleCorePath = "/{$vendorDir}/drupal/console-core/";
        $this->consolePath = "/{$vendorDir}/drupal/console/";
        $this->consoleLanguagePath = "/{$vendorDir}/drupal/console-%s/translations/";
    }

    protected function defineConstants($vendorDir)
    {
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

        if (!defined("DRUPAL_CONSOLE_LIBRARY")) {
            define(
            "DRUPAL_CONSOLE_LIBRARY",
            "/{$vendorDir}/drupal/%s/console/translations/%s"
            );
        }
    }

    /**
     * @return string
     */
    public function getConsoleCorePath()
    {
        return $this->consoleCorePath;
    }

    /**
     * @return string
     */
    public function getConsolePath()
    {
        return $this->consolePath;
    }

    /**
     * @return string
     */
    public function getConsoleLanguagePath()
    {
        return $this->consoleLanguagePath;
    }
}
