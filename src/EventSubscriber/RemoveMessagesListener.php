<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\RemoveMessagesListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Drupal\Console\Core\Utils\MessageManager;

/**
 * Class RemoveMessagesListener
 *
 * @package Drupal\Console\Core\EventSubscriber
 */
class RemoveMessagesListener implements EventSubscriberInterface
{
    /**
     * @var MessageManager
     */
    protected $messageManager;

    /**
     * ShowGenerateInlineListener constructor.
     *
     * @param MessageManager $messageManager
     */
    public function __construct(
        MessageManager $messageManager
    ) {
        $this->messageManager = $messageManager;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function removeMessages(ConsoleTerminateEvent $event)
    {
        if ($event->getExitCode() != 0) {
            return;
        }

        /* @var Command $command */
        $command = $event->getCommand();

        $commandName = $command->getName();

        $this->messageManager->remove($commandName);
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'removeMessages'];
    }
}
