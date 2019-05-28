<?php

namespace Drupal\Console\Core\Utils;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Finder\Finder;
use Dflydev\DotAccessConfiguration\YamlFileConfigurationBuilder;
use Dflydev\DotAccessConfiguration\ConfigurationInterface;
use Webmozart\PathUtil\Path;

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
    private $configurationDirectories = [];

    /**
     * @var array
     */
    private $sites = [];

    /**
     * @var array
     */
    private $configurationFiles = [];

    /**
     * @param $directory
     * @return $this
     */
    public function loadConfiguration($directory)
    {
        $this->locateConfigurationFiles();

        $this->applicationDirectory = $directory;
        if ($directory && is_dir($directory) && strpos($directory, 'phar:')!==0) {
            $this->addConfigurationFilesByDirectory(
                $directory . '/console/',
                true
            );
        }
        $input = new ArgvInput();
        $root = $input->getParameterOption(['--root']);
        if ($root && is_dir($root)) {
            $this->addConfigurationFilesByDirectory(
                $root. '/console/',
                true
            );
        }

        $builder = new YamlFileConfigurationBuilder(
            $this->configurationFiles['config']
        );

        $this->configuration = $builder->build();

        $extras = [
            'aliases',
            'mappings',
            'defaults'
        ];

        foreach ($extras as $extra) {
            $extraKey = 'application.extras.'.$extra;
            $extraFlag = $this->configuration->get($extraKey)?:'true';
            if ($extraFlag === 'true') {
                $this->appendExtraConfiguration($extra);
            }
        }

        return $this;
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    private function readSite($siteFile)
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
        $site = $target;
        $environment = null;
        $exploded = explode('.', $target, 2);

        if (count($exploded)>1) {
            $site = $exploded[0];
            $environment = $exploded[1];
        }
        $sites = $this->getSites($site);
        if (!array_key_exists($site, $sites)) {
            return [];
        }

        $targetInformation = $sites[$site];

        if ($environment) {
            if (!array_key_exists($environment, $sites[$site])) {
                return [];
            }

            $targetInformation = $sites[$site][$environment];
        }

        return $targetInformation;
    }

    /**
     * @return string
     */
    public function getApplicationDirectory()
    {
        return $this->applicationDirectory;
    }

    /**
     * Return the sites config directory.
     *
     * @return array
     */
    private function getSitesDirectories()
    {
        $sitesDirectories = array_map(
            function ($directory) {
                return $directory . 'sites';
            },
            $this->getConfigurationDirectories()
        );

        $sitesDirectories = array_filter(
            $sitesDirectories,
            function ($directory) {
                return is_dir($directory);
            }
        );

        $sitesDirectories = array_unique($sitesDirectories);

        return $sitesDirectories;
    }

    /**
     * @param string $commandName
     * @return mixed
     */
    public function readDrushEquivalents($commandName)
    {
        $equivalents = [];
        $drushMappings = Yaml::parse(
            file_get_contents(
                $this->applicationDirectory . DRUPAL_CONSOLE_CORE . 'config/drush.yml'
            )
        );

        foreach ($drushMappings['commands'] as $key => $commands) {
            foreach ($commands as $namespace => $command) {
                if ($command) {
                    $equivalents[$namespace] = $command;
                }
            }
        }

        if (!$commandName) {
            $drushMappings = [];
            foreach ($equivalents as $key => $alternative) {
                $drushMappings[] = [$key, $alternative];
            }

            return $drushMappings;
        }

        if (array_key_exists($commandName, $equivalents)) {
            return $equivalents[$commandName] ?: ' ';
        }

        return [];
    }

    public function getVendorCoreRoot()
    {
        $consoleCoreDirectory = dirname(dirname(dirname(__FILE__))) . '/';

        if (is_dir($consoleCoreDirectory)) {
            return $consoleCoreDirectory;
        }

        return null;
    }

    public function getVendorCoreDirectory()
    {
        $consoleCoreDirectory = dirname(dirname(dirname(__FILE__))) . '/config/';

        if (is_dir($consoleCoreDirectory)) {
            return $consoleCoreDirectory;
        }

        return null;
    }

    public function getSystemDirectory()
    {
        $systemDirectory = '/etc/console/';

        if (is_dir($systemDirectory)) {
            return $systemDirectory;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getConsoleDirectory()
    {
        $consoleDirectory = sprintf(
            '%s/.console/',
            $this->getHomeDirectory()
        );

        if (is_dir($consoleDirectory)) {
            return $consoleDirectory;
        }

        try {
            mkdir($consoleDirectory, 0777, true);
        } catch (\Exception $exception) {
            return null;
        }

        return $consoleDirectory;
    }

    /**
     * @param $includeVendorCore
     *
     * @return array
     */
    public function getConfigurationDirectories($includeVendorCore = false)
    {
        if ($this->configurationDirectories) {
            if ($includeVendorCore) {
                return array_merge(
                    [$this->getVendorCoreDirectory()],
                    $this->configurationDirectories
                );
            }

            return $this->configurationDirectories;
        }

        return [];
    }

    private function addConfigurationFilesByDirectory(
        $directory,
        $addDirectory = false
    ) {
        if ($addDirectory) {
            $this->configurationDirectories[] = $directory;
        }
        $configurationFiles = [
            'config' => 'config.yml',
            'drush' => 'drush.yml',
            'aliases' => 'aliases.yml',
            'mappings' => 'mappings.yml',
            'defaults' => 'defaults.yml',
        ];
        foreach ($configurationFiles as $key => $file) {
            $configFile = $directory.$file;
            if (is_file($configFile)) {
                $this->configurationFiles[$key][] = $configFile;
            }
        }
    }

    private function locateConfigurationFiles()
    {
        if ($this->getVendorCoreDirectory()) {
            $this->addConfigurationFilesByDirectory(
                $this->getVendorCoreDirectory()
            );
        }
        if ($this->getSystemDirectory()) {
            $this->addConfigurationFilesByDirectory(
                $this->getSystemDirectory(),
                true
            );
        }
        if ($this->getConsoleDirectory()) {
            $this->addConfigurationFilesByDirectory(
                $this->getConsoleDirectory(),
                true
            );
        }
    }

    /**
     * @return void
     */
    private function appendExtraConfiguration($type)
    {
        if (!array_key_exists($type, $this->configurationFiles)) {
            return;
        }

        $configData = [];
        foreach ($this->configurationFiles[$type] as $configFile) {
            if (file_get_contents($configFile)==='') {
                continue;
            }
            $parsed = Yaml::parse(file_get_contents($configFile));
            $configData = array_merge(
                $configData,
                is_array($parsed)?$parsed:[]
            );
        }

        if ($configData && array_key_exists($type, $configData)) {
            $this->configuration->set(
                'application.commands.'.$type,
                $configData[$type]
            );
        }
    }

    public function loadExtendConfiguration()
    {
        $directory = $this->getConsoleDirectory() . 'extend/';
        if (!is_dir($directory)) {
            return null;
        }

        $autoloadFile = $directory . 'vendor/autoload.php';
        if (!is_file($autoloadFile)) {
            return null;
        }
        include_once $autoloadFile;
        $extendFile = $directory . 'extend.console.config.yml';

        $this->importConfigurationFromFile($extendFile);
    }

    private function importConfigurationFromFile($configFile)
    {
        if (is_file($configFile) && file_get_contents($configFile)!='') {
            $builder = new YamlFileConfigurationBuilder([$configFile]);
            if ($this->configuration) {
                $this->configuration->import($builder->build());
            } else {
                $this->configuration = $builder->build();
            }
        }
    }

    /**
     * @param string $target
     *
     * @return array
     */
    public function getSites($target = "*")
    {
        if ($this->sites) {
            return $this->sites;
        }

        $sitesDirectories = $this->getSitesDirectories();

        if (!$sitesDirectories) {
            return [];
        }

        $finder = new Finder();
        $finder->in($sitesDirectories);
        $finder->name($target.".yml");

        foreach ($finder as $site) {
            $siteName = $site->getBasename('.yml');
            $environments = $this->readSite($site->getRealPath());

            if (!$environments || !is_array($environments)) {
                continue;
            }

            $this->sites[$siteName] = [
                'file' => $site->getRealPath()
            ];

            foreach ($environments as $environment => $config) {
                if (!array_key_exists('type', $config)) {
                    throw new \UnexpectedValueException(
                        sprintf(
                            "The 'type' parameter is required in sites configuration:\n %s.", $site->getPathname()
                        )
                    );
                }
                if ($config['type'] !== 'local') {
                    if (array_key_exists('host', $config)) {
                        $targetInformation['remote'] = true;
                    }

                    $config = array_merge(
                        $this->configuration->get('application.remote')?:[],
                        $config
                    );
                }

                $this->sites[$siteName][$environment] = $config;
            }
        }

        return $this->sites;
    }

    /**
     * Get config global as array.
     *
     * @return array
     */
    public function getConfigGlobalAsArray()
    {
        $filePath = sprintf(
            '%s/.console/config.yml',
            $this->getHomeDirectory()
        );

        $fs = new Filesystem();

        if (!$fs->exists($filePath)) {
            return null;
        }

        $yaml = new Parser();
        $globalConfig = $yaml->parse(file_get_contents($filePath), true);

        return $globalConfig;
    }

    /**
     * Update parameter in global config.
     *
     * @param  $configName
     * @param  $value
     * @return int
     */
    public function updateConfigGlobalParameter($configName, $value)
    {
        $parser = new Parser();
        $dumper = new Dumper();

        $userConfigFile = sprintf(
            '%s/.console/config.yml',
            $this->getHomeDirectory()
        );

        if (!file_exists($userConfigFile)) {
            return 1;
        }

        try {
            $userConfigFileParsed = $parser->parse(
                file_get_contents($userConfigFile)
            );
        } catch (\Exception $e) {
        }

        $parents = array_merge(['application'], explode('.', $configName));

        $nestedArray = new NestedArray();

        $nestedArray->setValue(
            $userConfigFileParsed,
            $parents,
            $value,
            true
        );

        try {
            $userConfigFileDump = $dumper->dump($userConfigFileParsed, 10);
        } catch (\Exception $e) {
        }

        try {
            file_put_contents($userConfigFile, $userConfigFileDump);
        } catch (\Exception $e) {
        }
    }

    /**
     * @return array
     */
    public function getConfigurationFiles()
    {
        return $this->configurationFiles;
    }

    public function getHomeDirectory()
    {
        return Path::getHomeDirectory();
    }
}
