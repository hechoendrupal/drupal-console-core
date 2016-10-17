<?php

/**
 * @file
 * Contains Drupal\Console\Command\ShowFileHelper.
 */

namespace Drupal\Console\Utils;

use Drupal\Console\Style\DrupalStyle;

/**
 * Class ShowFileHelper
 * @package Drupal\Console\Helper
 */
class ShowFile
{
    protected $root;
    protected $translator;

    /**
     * ShowFile constructor.
     * @param $root
     * @param $translator
     */
    public function __construct(
        $root,
        $translator
    ) {
        $this->root = $root;
        $this->translator = $translator;
    }

    /**
     * @param DrupalStyle $io
     * @param string      $files
     * @param boolean     $showPath
     */
    public function generatedFiles($io, $files, $showPath = true)
    {
        $pathKey = null;
        $path = null;
        if ($showPath) {
            $pathKey = 'application.user.messages.path';
            $path = $this->root;
        }
        $this->showFiles(
            $io,
            $files,
            'application.messages.files.generated',
            $pathKey,
            $path
        );
    }

    /**
     * @param DrupalStyle $io
     * @param string      $files
     * @param boolean     $showPath
     */
    public function copiedFiles($io, $files, $showPath = true)
    {
        $pathKey = null;
        $path = null;
        if ($showPath) {
            $pathKey = 'application.user.messages.path';
            $path = rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\').'/.console/';
        }
        $this->showFiles(
            $io,
            $files,
            'application.messages.files.copied',
            $pathKey,
            $path
        );
    }

    /**
     * @param DrupalStyle $io
     * @param array       $files
     * @param string      $headerKey
     * @param string      $pathKey
     * @param string      $path
     */
    private function showFiles($io, $files, $headerKey, $pathKey, $path)
    {
        if (!$files) {
            return;
        }

        $io->writeln($this->translator->trans($headerKey));

        if ($pathKey) {
            $io->info(
                sprintf('%s:', $this->translator->trans($pathKey)),
                false
            );
        }
        if ($path) {
            $io->comment($path, false);
        }
        $io->newLine();

        $index = 1;
        foreach ($files as $file) {
            $this->showFile($io, $file, $index);
            ++$index;
        }
    }

    /**
     * @param DrupalStyle $io
     * @param string      $file
     * @param int         $index
     */
    private function showFile(DrupalStyle $io, $file, $index)
    {
        $io->info(
            sprintf('%s -', $index),
            false
        );
        $io->comment($file, false);
        $io->newLine();
    }
}
