<?php

namespace Drupal\Console\Utils;

/**
 * Class DrupalChecker.
 */
class DrupalChecker
{
    /**
     * @param string $root
     * @param bool   $recursive
     *
     * @return bool
     */
    public function isValidRoot($root, $recursive = false)
    {
        if (!$root) {
            return false;
        }

        if ($root === '/' || preg_match('~^[a-z]:\\\\$~i', $root)) {
            return false;
        }

        $autoLoad = sprintf('%s%s%s', $root, DIRECTORY_SEPARATOR, 'autoload.php');
        $index = sprintf('%s%s%s', $root, DIRECTORY_SEPARATOR, 'index.php');

        if (file_exists($autoLoad) && file_exists($index)) {
            return true;
        }

        if ($recursive) {
            return $this->isValidRoot(
                realpath(
                    sprintf(
                        '%s%s..%s',
                        $root,
                        DIRECTORY_SEPARATOR,
                        DIRECTORY_SEPARATOR
                    )
                ),
                $recursive
            );
        }

        return false;
    }
}
