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

    protected $container;

    /**
     * @param mixed $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @param $key
     * @return null|object
     */
    public function has($key)
    {
        if (!$key) {
            return null;
        }

        return $this->container->has($key);
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
            return $this->container->get($key);
        }

        return null;
    }
}
