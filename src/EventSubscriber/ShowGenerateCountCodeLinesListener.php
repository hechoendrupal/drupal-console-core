<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\ShowGenerateCountCodeLinesListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Drupal\Console\Core\Utils\CountCodeLines;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Command\Command;
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
     * ShowGenerateChainListener constructor.
     *
     * @param CountCodeLines $countCodeLines
     */
    public function __construct(
        CountCodeLines $countCodeLines
    ) {
        $this->countCodeLines = $countCodeLines;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGenerateCountLines(ConsoleTerminateEvent $event)
    {
        if ($event->getExitCode() != 0) {
            return;
        }

        /* @var Command $command */
        $command = $event->getCommand();
        /* @var DrupalStyle $io */
        $io = new DrupalStyle($event->getInput(), $event->getOutput());

        $countCodeLines = $this->countCodeLines->getCountCodeLines();
        $io->writeln($countCodeLines);
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGenerateCountCodeLines'];
    }
}
