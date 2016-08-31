<?php

namespace Drupal\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * ConsoleApplication constructor.
     * @param ContainerInterface    $container
     * @param string                $name
     * @param string                $version
     */
    public function __construct(
        ContainerInterface$container,
        $name,
        $version
    )
    {
        $this->container = $container;
        parent::__construct($name, $version);
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

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->registerEvents();
        return parent::doRun(
            $input,
            $output
        );
    }

    private function registerEvents()
    {
        $dispatcher = new EventDispatcher();
        /* @todo Register listeners as services */
        $dispatcher->addSubscriber(
            new CallCommandListener(
                $this->container->get('console.chain_queue')
            )
        );
        $this->setDispatcher($dispatcher);
    }
}
