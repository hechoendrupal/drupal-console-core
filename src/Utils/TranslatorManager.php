<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Utils\TranslatorManager.
 */

namespace Drupal\Console\Core\Utils;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class TranslatorManager
 *
 * @package Drupal\Console\Core\Utils
 */
class TranslatorManager implements TranslatorManagerInterface
{
    /**
     * @var string
     */
    protected $language;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $coreLanguageRoot;

    /**
     * Translator constructor.
     */
    public function __construct()
    {
        $this->parser = new Parser();
        $this->filesystem = new Filesystem();
    }

    /**
     * @param $resource
     * @param string   $name
     */
    private function addResource($resource, $name = 'yaml')
    {
        $this->translator->addResource(
            $name,
            $resource,
            $this->language
        );
    }

    /**
     * @param $loader
     * @param string $name
     */
    private function addLoader($loader, $name = 'yaml')
    {
        $this->translator->addLoader(
            $name,
            $loader
        );
    }

    /**
     * @param $language
     * @param $directoryRoot
     *
     * @return array
     */
    private function buildCoreLanguageDirectory(
        $language,
        $directoryRoot
    ) {
        $coreLanguageDirectory =
            $directoryRoot .
            sprintf(
                DRUPAL_CONSOLE_LANGUAGE,
                $language
            );

        if (!is_dir($coreLanguageDirectory)) {
            return $this->buildCoreLanguageDirectory('en', $directoryRoot);
        }

        if (!$this->coreLanguageRoot) {
            $this->coreLanguageRoot = $directoryRoot;
        }

        return [$language, $coreLanguageDirectory];
    }

    /**
     * {@inheritdoc}
     */
    public function loadCoreLanguage($language, $directoryRoot)
    {
        $coreLanguageDirectory = $this->buildCoreLanguageDirectory(
            $language,
            $directoryRoot
        );

        $this->loadResource(
            $coreLanguageDirectory[0],
            $coreLanguageDirectory[1]
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function changeCoreLanguage($language)
    {
        return $this->loadCoreLanguage($language, $this->coreLanguageRoot);
    }

    /**
     * {@inheritdoc}
     */
    public function loadResource($language, $directoryRoot)
    {
        if (!is_dir($directoryRoot)) {
            return;
        }

        $this->language = $language;
        $this->translator = new Translator($this->language);
        $this->addLoader(new ArrayLoader(), 'array');
        $this->addLoader(new YamlFileLoader(), 'yaml');

        /* @TODO fallback to en */
        $finder = new Finder();
        $finder->files()
            ->name('*.yml')
            ->in($directoryRoot);

        foreach ($finder as $file) {
            $resource = $directoryRoot.'/'.$file->getBasename();
            $filename = $file->getBasename('.yml');

            // Handle application file different than commands
            if ($filename == 'application') {
                try {
                    $this->loadTranslationByFile($resource, 'application');
                } catch (ParseException $e) {
                    echo 'application.yml'.' '.$e->getMessage();
                }

                continue;
            }
            $key = 'commands.'.$filename;
            try {
                $this->loadTranslationByFile($resource, $key);
            } catch (ParseException $e) {
                echo $key.'.yml '.$e->getMessage();
            }
        }

        return;
    }

    /**
     * Load yml translation where filename is part of translation key.
     *
     * @param $resource
     * @param $resourceKey
     */
    protected function loadTranslationByFile($resource, $resourceKey = null)
    {
        $resourceParsed = $this->parser->parse(file_get_contents($resource));

        if ($resourceKey) {
            $parents = explode('.', $resourceKey);
            $resourceArray = [];
            $this->setResourceArray($parents, $resourceArray, $resourceParsed);
            $resourceParsed = $resourceArray;
        }

        $this->addResource($resourceParsed, 'array');
    }

    /**
     * @param $parents
     * @param $parentsArray
     * @param $resource
     *
     * @return mixed
     */
    private function setResourceArray($parents, &$parentsArray, $resource)
    {
        $ref = &$parentsArray;
        foreach ($parents as $parent) {
            $ref[$parent] = [];
            $previous = &$ref;
            $ref = &$ref[$parent];
        }

        $previous[$parent] = $resource;

        return $parentsArray;
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * {@inheritdoc}
     */
    public function trans($key)
    {
        return $this->translator->trans($key);
    }
}
