<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\ShowGenerateCountCodeLinesListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Drupal\Console\Core\Utils\CountCodeLines;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ShowGenerateCountCodeLinesListener
 *
 * @package Drupal\Console\Core\EventSubscriber
 */
class ShowGenerateCountCodeLinesListener implements EventSubscriberInterface
{

    /**
     * @var ShowGenerateChainListener
     */
    protected $countCodeLines;

    /**
     * @var TranslatorManagerInterface
     */
    protected $translator;

    /**
     * ShowGenerateChainListener constructor.
     *
     * @param TranslatorManagerInterface $translator
     *
     * @param CountCodeLines $countCodeLines
     *
     */
    public function __construct(
        TranslatorManagerInterface $translator,
        CountCodeLines $countCodeLines
    ) {
        $this->translator = $translator;
        $this->countCodeLines = $countCodeLines;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGenerateCountCodeLines(ConsoleTerminateEvent $event)
    {
        if ($event->getExitCode() != 0) {
            return;
        }

        /* @var DrupalStyle $io */
        $io = new DrupalStyle($event->getInput(), $event->getOutput());

        $countCodeLines = $this->countCodeLines->getCountCodeLines();
        if ($countCodeLines > 0) {
            $io->commentBlock(
                sprintf(
                    $this->translator->trans('application.messages.lines-code'),
                    $countCodeLines
                )
            );
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGenerateCountCodeLines'];
    }
}
