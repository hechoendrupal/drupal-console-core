<?php

use Webmozart\PathUtil\Path;

/**
 * @param string $path
 * @return null|string
 */
function calculateRealPath($path)
{
    if (!$path) {
        return null;
    }

    if (strpos($path, 'phar://')===0) {
        return $path;
    }

    if (realpath($path)) {
        return $path;
    }

    return Path::canonicalize($path);
}
