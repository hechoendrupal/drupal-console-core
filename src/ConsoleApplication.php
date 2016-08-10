<?php

namespace Drupal\Console;

use Symfony\Component\Console\Application;

/**
 * Class Application
 * @package Drupal\Console
 */
class ConsoleApplication extends Application {

    protected $container;

    public function getConfiguration()
    {
        if ($this->container) {
            return $this->container->get('console.configuration_manager')
                ->getConfiguration();
        }

        return null;
    }

    public function getTranslator()
    {
        if ($this->container) {
            return $this->container->get('console.translator_manager');
        }

        return null;
    }

    /**
     * @param $key string
     *
     * @return string
     */
    public function trans($key)
    {
        if ($this->getTranslator()) {
            return $this->getTranslator()->trans($key);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getContainer() {
        if ($this->container) {
            return $this->container;
        }

        return null;
    }
}