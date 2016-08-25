<?php

/**
 * @param string $path
 * @return null|string
 */
function calculateRealPath($path)
{
    if (!$path) {
        return null;
    }

    if (realpath($path)) {
        return $path;
    }

    return transformToRealPath($path);
}

/**
 * @param $path
 * @return string
 */
function transformToRealPath($path)
{
    if (strpos($path, '~') === 0) {
        $home = rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/');
        $path = preg_replace('/~/', $home, $path, 1);
    }

    if (!(strpos($path, '/') === 0)) {
        $path = sprintf('%s/%s', getcwd(), $path);
    }

    return realpath($path);
}
