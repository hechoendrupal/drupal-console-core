<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\ShowTipsListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ShowTipsListener
 *
 * @package Drupal\Console\Core\EventSubscriber
 */
class ShowTipsListener implements EventSubscriberInterface
{
    /**
     * @var TranslatorManagerInterface
     */
    protected $translator;

    /**
     * ShowGenerateChainListener constructor.
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
    public function showTips(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();
        $input = $command->getDefinition();
        /* @var DrupalStyle $io */
        $io = new DrupalStyle($event->getInput(), $event->getOutput());

        $learning = $input->getOption('learning');

        // pick randomly one of the tips (5 tips as maximum).
        $tips = $this->getTip($command->getName());

        if ($learning && $tips) {
            $io->commentBlock($tips);
        }
    }

    /**
     * @param $commandName
     * @return bool|string
     */
    private function getTip($commandName)
    {
        $get_tip = $this->translator
            ->trans('commands.'.str_replace(':', '.', $commandName).'.tips.0.tip');
        preg_match("/^commands./", $get_tip, $matches, null, 0);
        if (!empty($matches)) {
            return false;
        }

        $n = rand(0, 5);
        $get_tip = $this->translator
            ->trans('commands.'.str_replace(':', '.', $commandName).'.tips.' . $n . '.tip');
        preg_match("/^commands./", $get_tip, $matches, null, 0);

        if (empty($matches)) {
            return $get_tip;
        } else {
            return $this->getTip($commandName);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'showTips'];
    }
}
