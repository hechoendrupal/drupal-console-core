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

    private $missingConfigurationFiles = [];

    /**
     * @param $applicationDirectory
     * @return $this
     */
    public function loadConfiguration($applicationDirectory)
    {
        $this->applicationDirectory = $applicationDirectory;

        $input = new ArgvInput();
        $root = $input->getParameterOption(['--root'], null);

        $configFiles[] = $this->getConsoleDirectory().'config.yml';
        if ($this->getHomeDirectory() != getcwd()) {
            $configFiles[] = getcwd().'/console/config.yml';
        }

        if (stripos($applicationDirectory, '/bin/') <= 0) {
            $configFiles[] = $applicationDirectory.'/console/config.yml';
        }

        $files = [
            $applicationDirectory.'config.yml',
            $applicationDirectory.DRUPAL_CONSOLE_CORE.'config.yml',
            $applicationDirectory.DRUPAL_CONSOLE.'config.yml',
            $this->getConsoleDirectory().'config.yml',
            getcwd().'/console/config.yml',
            $applicationDirectory.'console/config.yml',
        ];

        if ($root) {
            $files[] = $root.'/console/config.yml';
            $configFiles[] = $root.'/console/config.yml';
        }

        $files = array_unique($files);
        $configFiles = array_unique($configFiles);

        foreach ($files as $key => $file) {
            if (!file_exists($file)) {
                unset($files[$key]);
                continue;
            }
            if (file_get_contents($file)==='') {
                unset($files[$key]);
                continue;
            }
        }

        foreach ($configFiles as $key => $file) {
            if (!file_exists($file)) {
                $this->missingConfigurationFiles[] = $file;
            } else {
                $this->missingConfigurationFiles = [];
                break;
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

    public function readSite($siteFile)
    {
        if (!file_exists($siteFile)) {
            return [];
        }

        return Yaml::parse(file_get_contents($siteFile));
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
            '%s/sites',
            $this->getConsoleDirectory()
        );
    }

    /**
     * @param string $commandName
     * @return mixed
     */
    public function readDrushEquivalents($commandName)
    {
        $equivalents = [];
        $aliasInformation = Yaml::parse(
            file_get_contents(
                $this->applicationDirectory.DRUPAL_CONSOLE_CORE.'config/drush.yml'
            )
        );

        foreach ($aliasInformation['commands'] as $key => $commands) {
            foreach ($commands as $drush => $console) {
                $equivalents[$drush] = $console;
            }
        }

        if (!$commandName) {
            $aliasInformation = [];
            foreach ($equivalents as $key => $alternative) {
                $aliasInformation[] = [$key, $alternative];
            }

            return $aliasInformation;
        }

        if (array_key_exists($commandName, $equivalents)) {
            return $equivalents[$commandName] ?: ' ';
        }

        return [];
    }

    public function getConsoleDirectory()
    {
        return sprintf('%s/.console/', $this->getHomeDirectory());
    }

    public function getMissingConfigurationFiles()
    {
        return $this->missingConfigurationFiles;
    }
}
