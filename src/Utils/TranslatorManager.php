<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\Translator.
 */

namespace Drupal\Console\Utils;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class TranslatorManager
 * @package Drupal\Console\Utils
 */
class TranslatorManager
{
    /**
     * @var string
     */
    private $language;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Filesystem
     */
    private $filesystem;

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

    private function buildCoreLanguageDirectory($language, $directoryRoot) {
        $languageDirectory = sprintf(
            '%svendor/drupal/console-%s/translations/',
            $directoryRoot,
            $language
        );

        if (!is_dir($languageDirectory)) {
            return $this->buildCoreLanguageDirectory('en', $directoryRoot);
        }

        return $languageDirectory;
    }

    /**
     * @param $language
     * @param $directoryRoot
     * @return $this
     */
    public function loadCoreLanguage($language, $directoryRoot)
    {
        $directoryRoot = $this->buildCoreLanguageDirectory(
            $language,
            $directoryRoot
        );

        $this->loadResource(
            $language,
            $directoryRoot
        );

        return $this;
    }

    /**
     * @param $language
     * @param $directoryRoot
     *
     * @return mixed
     */
    public function loadResource($language, $directoryRoot)
    {
        if (!is_dir($directoryRoot)) {
            return false;
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
    }

    /**
     * Load yml translation where filename is part of translation key.
     *
     * @param $resource
     * @param $resourceKey
     */
    private function loadTranslationByFile($resource, $resourceKey = null)
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
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function trans($key)
    {
        return $this->translator->trans($key);
    }
}
