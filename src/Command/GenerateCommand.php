<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\GenerateCommand.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Class GenerateCommand
 *
 * @package Drupal\Console\Core\Command
 */
abstract class GenerateCommand extends Command
{
    protected function validateFileExists(
        Filesystem $fs,
        $sourceFiles,
        $stopOnException = true
    ) {
        $notFound = [];
        if (!is_array($sourceFiles)) {
            $sourceFiles = [$sourceFiles];
        }
        foreach ($sourceFiles as $sourceFile) {
            if ($fs->exists($sourceFile)) {
                return $sourceFile;
            }

            $notFound[] = Path::makeRelative(
                $sourceFile,
                $this->drupalFinder->getComposerRoot()
            );
        }

        if ($stopOnException) {
            $this->createException(
                'File(s): ' . implode(', ', $notFound) . ' not found.'
            );
        }

        return null;
    }

    protected function backUpFile(Filesystem $fs, $fileName)
    {
        $fileNameBackup = $fileName.'.original';
        if ($fs->exists($fileName)) {
            if ($fs->exists($fileNameBackup)) {
                $fs->remove($fileName);
                return;
            }

            $fs->rename(
                $fileName,
                $fileNameBackup,
                TRUE
            );

            $fileNameBackup = Path::makeRelative(
                $fileNameBackup,
                $this->drupalFinder->getComposerRoot()
            );

            $this->getIo()->success(
                'File ' . $fileNameBackup . ' created.'
            );

        }
    }

    protected function showFileCreatedMessage($fileName) {
        $fileName = Path::makeRelative(
            $fileName,
            $this->drupalFinder->getComposerRoot()
        );

        $this->getIo()->success('File: ' . $fileName . ' created.');
    }
}
