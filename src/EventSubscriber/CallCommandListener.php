<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\CallCommandListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class CallCommandListener
 * @package Drupal\Console\Core\EventSubscriber
 */
class CallCommandListener implements EventSubscriberInterface
{
    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * CallCommandListener constructor.
     * @param ChainQueue $chainQueue
     */
    public function __construct(ChainQueue $chainQueue)
    {
        $this->chainQueue = $chainQueue;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function callCommands(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();

        /* @var DrupalStyle $io */
        $io = new DrupalStyle($event->getInput(), $event->getOutput());

        if (!$command instanceof Command) {
            return;
        }

        $application = $command->getApplication();
        $commands = $this->chainQueue->getCommands();

        if (!$commands) {
            return;
        }

        foreach ($commands as $chainedCommand) {
            $callCommand = $application->find($chainedCommand['name']);

            if (!$callCommand) {
                continue;
            }

            $input = new ArrayInput($chainedCommand['inputs']);
            if (!is_null($chainedCommand['interactive'])) {
                $input->setInteractive($chainedCommand['interactive']);
            }

            $io->text($chainedCommand['name']);
            $callCommand->run($input, $io);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'callCommands'];
    }
}
