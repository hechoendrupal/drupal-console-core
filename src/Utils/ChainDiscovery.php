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
 *
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
            $chainContent = $this->getFileContents($file);
            $chain = Yaml::parse($chainContent);
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
                'commands' => $chain['commands'],
                'placeholders' => [
                    'inline' => $this->extractInlinePlaceHolders($chainContent),
                    'environment' => $this->extractEnvironmentPlaceHolders($chainContent)
                ],
            ];
        }

        return $chainCommands;
    }

    /**
     * Helper to load and clean up the chain file.
     *
     * @param string $file The file name
     *
     * @return string $contents The contents of the file
     */
    public function getFileContents($file)
    {
        $contents = file_get_contents($file);

        // Remove lines with comments.
        $contents = preg_replace('![ \t]*#.*[ \t]*[\r|\r\n|\n]!', PHP_EOL, $contents);
        //  Strip blank lines
        $contents = preg_replace("/(^[\r\n]*|[\r\n]+)[\t]*[\r\n]+/", PHP_EOL, $contents);

        return $contents;
    }

    private function extractPlaceHolders($chainContent, $identifier)
    {
        $placeHoldersExtracted = [];
        $regex = '/\\'.$identifier.'{{(.*?)}}/';
        preg_match_all(
            $regex,
            $chainContent,
            $placeHoldersExtracted
        );

        if (!$placeHoldersExtracted) {
            return [];
        }

        return array_unique($placeHoldersExtracted[1]);
    }

    public function extractInlinePlaceHolders($chainContent)
    {
        $extractedInlinePlaceHolders = $this->extractPlaceHolders($chainContent, '%');
        $extractedVars = $this->extractVars($chainContent);

        $inlinePlaceHolders = [];
        foreach ($extractedInlinePlaceHolders as $key => $inlinePlaceHolder) {
            $placeholderValue = null;
            if (array_key_exists($inlinePlaceHolder, $extractedVars)) {
                $placeholderValue = $extractedVars[$inlinePlaceHolder];
            }
            $inlinePlaceHolders[$inlinePlaceHolder] = $placeholderValue;
        }

        return $inlinePlaceHolders;
    }

    public function extractEnvironmentPlaceHolders($chainContent)
    {
        return $this->extractPlaceHolders($chainContent, '$');
    }

    public function extractVars($chainContent) {
        $chain = Yaml::parse($chainContent);
        if (!array_key_exists('vars', $chain)) {
            return [];
        }

        return $chain['vars'];
    }
}
