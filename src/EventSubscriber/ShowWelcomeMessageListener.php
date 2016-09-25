<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowWelcomeMessageListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Utils\TranslatorManager;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ShowWelcomeMessageListener
 * @package Drupal\Console\EventSubscriber
 */
class ShowWelcomeMessageListener implements EventSubscriberInterface
{
    /**
     * @var TranslatorManager
     */
    protected $translator;

    /**
     * ShowWelcomeMessageListener constructor.
     * @param TranslatorManager $translator
     */
    public function __construct(
        TranslatorManager $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function showWelcomeMessage(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();

        /* @var DrupalStyle $io */
        $io = new DrupalStyle($event->getInput(), $event->getOutput());

        $welcomeMessageKey = 'commands.'.str_replace(':', '.', $command->getName()).'.welcome';
        $welcomeMessage = $this->translator->trans($welcomeMessageKey);

        if ($welcomeMessage != $welcomeMessageKey) {
            $io->text($welcomeMessage);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'showWelcomeMessage'];
    }
}
