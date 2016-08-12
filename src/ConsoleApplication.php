<?php

namespace Drupal\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drupal\Console\EventSubscriber\CallCommandListener;
/**
 * Class Application
 * @package Drupal\Console
 */
class ConsoleApplication extends Application
{
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

    public function getContainer()
    {
        if ($this->container) {
            return $this->container;
        }

        return null;
    }

    public function doRun(InputInterface $input, OutputInterface $output) {

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new CallCommandListener());
        $this->setDispatcher($dispatcher);

        echo 'doRun' . PHP_EOL;

        return parent::doRun(
            $input,
            $output
        );
    }

//    public function registerEventDispatcher($events = []) {
//        $dispatcher = new EventDispatcher();
//        $dispatcher->addSubscriber(new CallCommandListener());
//        $this->setDispatcher($dispatcher);
//    }
}
