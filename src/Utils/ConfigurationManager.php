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

    /**
     * @var string
     */
    private $applicationDirectory = null;

    /**
     * @var array
     */
    private $missingConfigurationFiles = [];

    /**
     * @var array
     */
    private $configurationDirectories = [];

    /**
     * @param $applicationDirectory
     * @return $this
     */
    public function loadConfiguration($applicationDirectory)
    {
        $homeConfig = $this->getHomeDirectory() . '/.console/';
        if (!is_dir($homeConfig)) {
            mkdir($homeConfig, 0777);
        }

        $this->applicationDirectory = $applicationDirectory;
        $input = new ArgvInput();
        $root = $input->getParameterOption(['--root'], null);

        $configurationDirectories[] = $applicationDirectory;
        $configurationDirectories[] = $applicationDirectory.DRUPAL_CONSOLE_CORE;
        $configurationDirectories[] = $applicationDirectory.DRUPAL_CONSOLE;
        $configurationDirectories[] = '/etc/console/';
        $configurationDirectories[] = $this->getHomeDirectory() . '/.console/';
        $configurationDirectories[] = $applicationDirectory .'/console/';
        $configurationDirectories[] = getcwd().'/console/';
        if ($root) {
            $configurationDirectories[] = $root . '/console/';
        }
        $configurationDirectories = array_unique($configurationDirectories);

        $configurationFiles = [];
        foreach ($configurationDirectories as $configurationDirectory) {
            $file =  $configurationDirectory . 'config.yml';

            if (is_dir($configurationDirectory)
                && stripos($configurationDirectory, '/vendor/') <= 0
                && stripos($configurationDirectory, '/bin/') <= 0
                && stripos($configurationDirectory, 'console/') > 0
            ) {
                $this->configurationDirectories[] = str_replace('//', '/', $configurationDirectory);
            }

            if (!file_exists($file)) {
                $this->missingConfigurationFiles[] = $file;
                continue;
            }
            if (file_get_contents($file)==='') {
                $this->missingConfigurationFiles[] = $file;
                continue;
            }

            $configurationFiles[] = $file;
        }

        $builder = new YamlFileConfigurationBuilder($configurationFiles);
        $this->configuration = $builder->build();
        $this->appendCommandAliases();

        if ($configurationFiles) {
            $this->missingConfigurationFiles = [];
        }

        return $this;
    }

    public function loadConfigurationFromDirectory($directory)
    {
        $builder = new YamlFileConfigurationBuilder([$directory.'/console/config.yml']);

        return $builder->build();
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

        return realpath(rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\'));
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

    /**
     * @return string
     */
    public function getConsoleDirectory()
    {
        return sprintf('%s/.console/', $this->getHomeDirectory());
    }

    /**
     * @return array
     */
    public function getMissingConfigurationFiles()
    {
        return $this->missingConfigurationFiles;
    }

    /**
     * @return array
     */
    public function getConfigurationDirectories()
    {
        return $this->configurationDirectories;
    }

    /**
     * @return string
     */
    public function appendCommandAliases()
    {
        $configurationDirectories = array_merge(
            [$this->applicationDirectory . DRUPAL_CONSOLE_CORE . 'config/dist/'],
            $this->configurationDirectories
        );
        $aliases = [];
        foreach ($configurationDirectories as $directory) {
            $aliasFile = $directory . 'aliases.yml';
            if (file_exists($aliasFile)) {
                $aliases = array_merge(
                    $aliases,
                    Yaml::parse(file_get_contents($aliasFile))
                );
            }
        }
        if (array_key_exists('commands',$aliases) && array_key_exists('aliases',$aliases['commands'])) {
            $this->configuration->set(
                'application.commands.aliases',
                $aliases['commands']['aliases']
            );
        }
    }

    public function loadExtendLibraries()
    {
        $directory = $this->getHomeDirectory() . '/.console/extend/';
        if (!is_dir($directory)) {
            return null;
        }

        $autoloadFile = $directory . 'vendor/autoload.php';
        if (!is_file($autoloadFile)) {
            return null;
        }
        include_once $autoloadFile;

        $extendFile = $directory . 'extend.yml';
        if (!is_file($extendFile)) {
            return null;
        }
        $builder = new YamlFileConfigurationBuilder([$extendFile]);

        $this->configuration->import($builder->build());
    }
}
