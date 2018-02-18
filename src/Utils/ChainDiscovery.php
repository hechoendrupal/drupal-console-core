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
     * @var string
     */
    protected $appRoot;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var MessageManager
     */
    protected $messageManager;

    /**
     * @var TranslatorManagerInterface
     */
    protected $translatorManager;

    /**
     * @var array
     */
    private $directories = [];

    /**
     * @var array
     */
    private $files = [];

    /**
     * @var array
     */
    private $filesPerDirectory = [];

    const INLINE_REGEX = '/{{(.*?)}}/';
    const ENV_REGEX =  '/%env\((.*?)\)%/';

    /**
     * @var array
     */
    private $inlineRegexLegacy = [
        '/%{{(.*?)}}/',
        '/%{{ (.*?) }}/',
    ];

    /**
     * @var array
     */
    private $envRegexLegacy = [
        '/\${{(.*?)}}/',
        '/\${{ (.*?) }}/',
        '/%env\((.*?)\)%/',
        '/% env\((.*?)\) %/'
    ];

    /**
     * ChainDiscovery constructor.
     *
     * @param string                     $appRoot
     * @param ConfigurationManager       $configurationManager
     * @param MessageManager             $messageManager
     * @param TranslatorManagerInterface $translatorManager
     */
    public function __construct(
        $appRoot,
        ConfigurationManager $configurationManager,
        MessageManager $messageManager,
        TranslatorManagerInterface $translatorManager
    ) {
        $this->appRoot = $appRoot;
        $this->configurationManager = $configurationManager;
        $this->messageManager = $messageManager;
        $this->translatorManager = $translatorManager;

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
     * @deprecated
     *
     * @return array
     */
    public function getChainFiles()
    {
        return $this->getFiles();
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        if ($this->files) {
            return $this->files;
        }

        foreach ($this->directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            $finder = new Finder();
            $finder->files()
                ->name('*.yml')
                ->in($directory);
            foreach ($finder as $file) {

                $filePath = $file->getRealPath();
                if (empty($filePath)) {
                    $filePath = $directory . $file->getBasename();
                }

                if (!is_file($filePath)) {
                    continue;
                }
                $this->files[$filePath] = [
                    'directory' => $directory,
                    'file_name' => $file->getBasename(),
                    'messages' => []
                ];

                $this->getFileContents($filePath);
                $this->getFileMetadata($filePath);

                if ($this->files[$filePath]['messages']) {
                    $this->messageManager->comment(
                        $filePath,
                        0,
                        'list'
                    );
                    $this->messageManager->listing(
                        $this->files[$filePath]['messages'],
                        0,
                        'list'
                    );
                }

                $this->filesPerDirectory[$directory][] = $file->getBasename();
            }
        }

        return $this->files;
    }

    /**
     * @return array
     */
    public function getFilesPerDirectory()
    {
        return $this->filesPerDirectory;
    }

    /**
     * @return array
     */
    public function getChainCommands()
    {
        $chainCommands = [];
        $files = array_keys($this->getFiles());
        foreach ($files as $file) {
            $chainMetadata = $this->getFileMetadata($file);

            if (!$chainMetadata) {
                continue;
            }

            $name = $chainMetadata['command']['name'];
            $description = $chainMetadata['command']['description'];

            $chainCommands[$name] = [
                'description' => $description,
                'file' => $file,
            ];

            $this->files[$file]['command'] = $name;
            $this->files[$file]['description'] = $description;
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
        if ($metadata = $this->getCacheMetadata($file)) {
            return $metadata;
        }

        $contents = $this->getFileContents($file);

        $line = strtok($contents, PHP_EOL);
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

        $chainMetadata = $this->processMetadata($metadata);

        if (!$chainMetadata) {
            $this->files[$file]['messages'][] = $this->translatorManager
                ->trans('commands.chain.messages.metadata-registration');
            return [];
        }

        $this->files[$file]['metadata'] = $chainMetadata;

        return $chainMetadata;
    }

    private function processMetadata($metadata) {
        if (!$metadata) {
            return [];
        }

        $chainMetadata = Yaml::parse($metadata);

        if (!$chainMetadata || !is_array($chainMetadata)) {
            return [];
        }

        if (!array_key_exists('command', $chainMetadata) || !is_array($chainMetadata['command'])) {
            return [];
        }

        if (!array_key_exists('name', $chainMetadata['command'])) {
            return [];
        }

        if (!array_key_exists('description', $chainMetadata['command'])) {
            $chainMetadata['command']['description']  = '';
        }

        return $chainMetadata;
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
        if (empty($file)) {
            return '';
        }

        if ($contents = $this->getCacheContent($file)) {
            return $contents;
        }

        $contents = file_get_contents($file);

        // Support BC for legacy inline variables.
        $inlineLegacyContent = preg_replace(
            $this->inlineRegexLegacy,
            '{{ $1 }}',
            $contents
        );

        if ($contents !== $inlineLegacyContent) {
            $this->files[$file]['messages'][] = $this->translatorManager
                ->trans('commands.chain.messages.legacy-inline');
            $contents = $inlineLegacyContent;
        }

        // Support BC for legacy environment variables.
        $envLegacyContent = preg_replace(
            $this->envRegexLegacy,
            '{{ env("$1") }}',
            $contents
        );

        if ($contents !== $envLegacyContent) {
            $this->files[$file]['messages'][] = $this->translatorManager
                ->trans('commands.chain.messages.legacy-environment');
            $contents = $envLegacyContent;
        }

        // Remove lines with comments.
        $contents = preg_replace(
            '![ \t]*#.*[ \t]*[\r|\r\n|\n]!',
            PHP_EOL,
            $contents
        );

        //  Strip blank lines
        $contents = preg_replace(
            "/(^[\r\n]*|[\r\n]+)[\t]*[\r\n]+/",
            PHP_EOL,
            $contents
        );

        $this->files[$file]['content'] = $contents;

        return $contents;
    }

    private function getCacheContent($file)
    {
        if (!array_key_exists($file, $this->files)) {
            return null;
        }

        if (!array_key_exists('content', $this->files[$file])) {
            return null;
        }

        return $this->files[$file]['content'];
    }

    private function getCacheMetadata($file)
    {
        if (!array_key_exists($file, $this->files)) {
            return null;
        }

        if (!array_key_exists('metadata', $this->files[$file])) {
            return null;
        }

        return $this->files[$file]['metadata'];
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
