<?php

namespace Drupal\Console\Utils;

use Symfony\Component\Yaml\Yaml;
use Dflydev\DotAccessConfiguration\YamlFileConfigurationBuilder;
use Dflydev\DotAccessConfiguration\ConfigurationInterface;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * Class ConfigurationManager.
 */
class ConfigurationManager
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration = null;

    private $applicationDirectory = null;

    /**
     * @param $applicationDirectory
     * @return $this
     */
    public function loadConfiguration($applicationDirectory)
    {
        $this->applicationDirectory = $applicationDirectory;

        $input = new ArgvInput();
        $root = $input->getParameterOption(['--root'], null);

        $files = [
            $applicationDirectory.'config.yml',
            $applicationDirectory.DRUPAL_CONSOLE_CORE.'config.yml',
            $applicationDirectory.DRUPAL_CONSOLE_CORE.'config/dist/config.yml',
            $applicationDirectory.DRUPAL_CONSOLE.'config.yml',
            $applicationDirectory.DRUPAL_CONSOLE.'config/dist/config.yml',
            $this->getHomeDirectory().'/.console/config.yml',
            getcwd().'/console/config.yml',
            $applicationDirectory.'console/config.yml',
        ];

        if ($root) {
            $files[] = $root.'/console/config.yml';
        }

        foreach ($files as $key => $file) {
            if (!file_exists($file)) {
                unset($files[$key]);
            }
        }

        $builder = new YamlFileConfigurationBuilder($files);

        $this->configuration = $builder->build();

        return $this;
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param $target
     *
     * @return array
     */
    public function readTarget($target)
    {
        if (!$target || !strpos($target, '.')) {
            return [];
        }

        $site = explode('.', $target)[0];
        $env = explode('.', $target)[1];

        $siteFile = sprintf(
            '%s%s%s.yml',
            $this->getSitesDirectory(),
            DIRECTORY_SEPARATOR,
            $site
        );

        if (!file_exists($siteFile)) {
            return [];
        }

        $targetInformation = Yaml::parse(file_get_contents($siteFile));

        if (!array_key_exists($env, $targetInformation)) {
            return [];
        }

        $targetInformation = $targetInformation[$env];

        if (array_key_exists('host', $targetInformation) && $targetInformation['host'] != 'local') {
            $targetInformation['remote'] = true;
        }

        return array_merge(
            $this->configuration->get('application.remote'),
            $targetInformation
        );
    }

    /**
     * Return the user home directory.
     *
     * @return string
     */
    public function getHomeDirectory()
    {
        if (function_exists('posix_getuid')) {
            return posix_getpwuid(posix_getuid())['dir'];
        }

        return rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\');
    }

    /**
     * @return string
     */
    public function getApplicationDirectory()
    {
        return $this->applicationDirectory;
    }

    /**
     * Return the site config directory.
     *
     * @return string
     */
    public function getSitesDirectory()
    {
        return sprintf(
            '%s%s.console%ssites',
            $this->getHomeDirectory(),
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );
    }
}
