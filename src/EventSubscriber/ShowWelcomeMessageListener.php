<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\ShowWelcomeMessageListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ShowWelcomeMessageListener
 *
 * @package Drupal\Console\Core\EventSubscriber
 */
class ShowWelcomeMessageListener implements EventSubscriberInterface
{
    /**
     * @var TranslatorManagerInterface
     */
    protected $translator;

    /**
     * ShowWelcomeMessageListener constructor.
     *
     * @param TranslatorManagerInterface $translator
     */
    public function __construct(
        TranslatorManagerInterface $translator
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
