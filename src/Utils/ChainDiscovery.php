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

    const INLINE_REGEX = '/{{(.*?)}}/';
    const ENV_REGEX =  '/%env\((.*?)\)%/';

    const ENV_REGEX_LEGACY = [
        '/%env\((.*?)\)%/',
        '/% env\((.*?)\) %/',
        '/{{ env\((.*?)\) }}/'
    ];

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

        $directories = array_map(
            function ($item) {
                return $item . 'chain/';
            },
            $configurationManager->getConfigurationDirectories(true)
        );

        $this->addDirectories($directories);
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
                    '%s%s',
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
            $chainContent = $this->getFileMetadata($file);

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
            ];
        }

        return $chainCommands;
    }

    public function parseContent($file, $placeholders)
    {
        $placeholders = array_filter(
            $placeholders,
            function ($element) {
                return $element !== null;
            }
        );

        unset($placeholders['file']);
        unset($placeholders['placeholder']);

        $contents = $this->getFileContents($file);

        $loader = new \Twig_Loader_Array(
            [
            'chain' => $contents,
            ]
        );

        $twig = new \Twig_Environment($loader);
        $envFunction = new \Twig_SimpleFunction(
            'env',
            function ($variableName) {
                $variableValue = getenv($variableName);
                if (!empty($variableValue)) {
                    return $variableValue;
                }

                return '%env('.$variableName.')%';
            }
        );
        $twig->addFunction($envFunction);

        $variables = $this->extractInlinePlaceHolderNames($contents);

        foreach ($variables as $variable) {
            if (!array_key_exists($variable, $placeholders)) {
                $placeholders[$variable] = '{{ ' . $variable . ' }}';
            }
        }

        return $twig->render('chain', $placeholders);
    }

    public function getFileMetadata($file)
    {
        $contents = $this->getFileContents($file);

        $line = strtok($contents, PHP_EOL);
        $metadata = '';
        $index = 0;
        while ($line !== false) {
            $index++;

            if ($index === 1 && $line !== 'command:') {
                break;
            }

            if ($index > 1 && substr($line, 0, 2) !== "  ") {
                break;
            }

            $metadata .= $line . PHP_EOL;
            $line = strtok(PHP_EOL);
        }

        return $metadata;
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

        // Support backwards compatibility with legacy format for inline/environment variables.
        $originalContents = $contents;
        $contents = preg_replace('|%{{(.*?)}}|', '{{$1}}', $originalContents);
        if ($contents != $originalContents) {
          print 'Please edit your chain files and change the placeholders for Inline variables from %{{(name}} to {{name}}' . PHP_EOL;
        }
        $originalContents = $contents;
        $contents = preg_replace('|\${{(.*?)}}|', '%env($1)%', $originalContents);
        if ($contents != $originalContents) {
          print 'Please edit your chain files and change the placeholders for Inline variables from ${{name}} to %env(name)%' . PHP_EOL;
        }

        return $contents;
    }

    private function extractPlaceHolders(
        $chainContent,
        $regex
    ) {
        $placeHoldersExtracted = [];
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

    public function extractInlinePlaceHolderNames($content)
    {
        preg_match_all($this::INLINE_REGEX, $content, $matches);

        return array_map(
            function ($item) {
                return trim($item);
            },
            $matches[1]
        );
    }

    public function extractInlinePlaceHolders($chainContent)
    {
        $extractedInlinePlaceHolders = $this->extractPlaceHolders(
            $chainContent,
            $this::INLINE_REGEX
        );
        $extractedVars = $this->extractVars($chainContent);

        $inlinePlaceHolders = [];
        foreach ($extractedInlinePlaceHolders as $key => $inlinePlaceHolder) {
            $inlinePlaceHolder = trim($inlinePlaceHolder);
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
        return $this->extractPlaceHolders($chainContent, $this::ENV_REGEX);
    }

    public function extractVars($chainContent)
    {
        $chain = Yaml::parse($chainContent);
        if (!array_key_exists('vars', $chain)) {
            return [];
        }

        return $chain['vars'];
    }
}
