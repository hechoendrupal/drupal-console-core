<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\ContainerAwareCommandTrait.
 */

namespace Drupal\Console\Command\Shared;

/**
 * Class CommandTrait
 * @package Drupal\Console\Command
 */
trait ContainerAwareCommandTrait
{
    use CommandTrait;

    /**
     * @param $key
     * @return null|object
     */
    public function has($key)
    {
        if (!$key) {
            return null;
        }

        return $this->getContainer()->has($key);
    }

    /**
     * @param $key
     * @return null|object
     */
    public function get($key)
    {
        if (!$key) {
            return null;
        }

        if ($this->has($key)) {
            return $this->getContainer()->get($key);
        }

        return null;
    }
}
