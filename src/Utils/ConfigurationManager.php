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
     * ConfigurationReader constructor.
     */
    public function __construct()
    {
        $input = new ArgvInput();
        $root = $input->getParameterOption(['--root'], null);

        $files = [
            __DIR__.'/../../config.yml',
            $this->getHomeDirectory().'/.console/config.yml',
            getcwd().'/console/config.yml',
        ];

        if ($root) {
            $files[] = $root.'/console/config.yml';
        }

        $builder = new YamlFileConfigurationBuilder($files);

        $this->configuration = $builder->build();
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
    private function getHomeDirectory()
    {
        if (function_exists('posix_getuid')) {
            return posix_getpwuid(posix_getuid())['dir'];
        }

        return rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\');
    }

    /**
     * Return the site config directory.
     *
     * @return string
     */
    private function getSitesDirectory()
    {
        return sprintf(
            '%s%s.console%ssites',
            $this->getHomeDirectory(),
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );
    }
}
