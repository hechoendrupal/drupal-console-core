<?php
/**
 * @file
 * Contains Drupal\Console\Core\Utils\Site.
 */

namespace Drupal\Console\Core\Utils;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ChainDiscovery
 * @package Drupal\Console\Core\Utils
 */
class ChainDiscovery
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var array
     */
    private $directories = [];

    /**
     * ChainDiscovery constructor.
     *
     * @param string               $appRoot
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        $appRoot,
        ConfigurationManager $configurationManager
    ) {
        $this->appRoot = $appRoot;
        $this->configurationManager = $configurationManager;

        $this->addDirectories(
            [
            $this->configurationManager->getHomeDirectory() . DIRECTORY_SEPARATOR . '.console'. DIRECTORY_SEPARATOR .'chain',
            $this->appRoot . DIRECTORY_SEPARATOR . 'console'. DIRECTORY_SEPARATOR .'chain',
            $this->appRoot . DIRECTORY_SEPARATOR . '.console'. DIRECTORY_SEPARATOR .'chain',
            ]
        );
    }

    /**
     * @param array $directories
     */
    public function addDirectories(array $directories)
    {
        $this->directories = array_merge($this->directories, $directories);
    }

    /**
     * @param bool $onlyFiles
     * @return array
     */
    public function getChainFiles($onlyFiles = false)
    {
        $chainFiles = [];
        foreach ($this->directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            $finder = new Finder();
            $finder->files()
                ->name('*.yml')
                ->in($directory);
            foreach ($finder as $file) {
                $chainFiles[$file->getPath()][] = sprintf(
                    '%s/%s',
                    $directory,
                    $file->getBasename()
                );
            }
        }

        if ($onlyFiles) {
            $files = [];
            foreach ($chainFiles as $chainDirectory => $chainFileList) {
                $files = array_merge($files, $chainFileList);
            }
            return $files;
        }

        return $chainFiles;
    }

    /**
     * @return array
     */
    public function getChainCommands()
    {
        $chainCommands = [];
        $files = $this->getChainFiles(true);
        foreach ($files as $file) {
            $chain = Yaml::parse(file_get_contents($file));
            if (!array_key_exists('command', $chain)) {
                continue;
            }
            if (!array_key_exists('name', $chain['command'])) {
                continue;
            }
            $name = $chain['command']['name'];
            $description = '';
            if (array_key_exists('description', $chain['command'])) {
                $description = $chain['command']['description'];
            }
            $chainCommands[$name] = [
                'description' => $description,
                'file' => $file,
            ];
        }

        return $chainCommands;
    }
}
