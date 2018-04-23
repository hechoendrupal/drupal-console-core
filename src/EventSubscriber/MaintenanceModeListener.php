<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\MaintenanceModeListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class MaintenanceModeListener
 *
 * @package Drupal\Console\Core\EventSubscriber
 */
class MaintenanceModeListener implements EventSubscriberInterface
{
    /**
     * @var TranslatorManagerInterface
     */
    protected $translator;

    /**
     * @var StateInterface
     */
    protected $state;

    /**
     * MaintenanceModeListener constructor.
     *
     * @param TranslatorManagerInterface $translator
     * @param StateInterface $state
     */
    public function __construct(
        TranslatorManagerInterface $translator,
        StateInterface $state
    ) {
        $this->translator = $translator;
        $this->state = $state;
    }

    /**
     * Enable maintenance mode.
     *
     * @param ConsoleEvent $event
     */
    public function enableMaintenanceMode(ConsoleEvent $event)
    {
        $this->switchMaintenanceMode($event, 'on');
    }

    /**
     * Disable maintenance mode.
     *
     * @param ConsoleEvent $event
     */
    public function disableMaintenanceMode(ConsoleEvent $event)
    {
        $this->switchMaintenanceMode($event, 'off');
    }

    /**
     * Switch maintenance mode.
     *
     * @param ConsoleEvent $event
     * @param string $mode
     */
    public function switchMaintenanceMode(ConsoleEvent $event, $mode)
    {
        /* @var Command $command */
        $command = $event->getCommand();

        if ($command->isMaintenance()) {

            /* @var DrupalStyle $io */
            $io = new DrupalStyle($event->getInput(), $event->getOutput());
            $stateName = 'system.maintenance_mode';
            $modeMessage = null;

            if ($mode == 'on') {
                $this->state->set($stateName, true);
                $modeMessage = $this->translator->trans('commands.site.maintenance.messages.maintenance-on');
            }

            if ($mode == 'off') {
                $this->state->set($stateName, false);
                $modeMessage = $this->translator->trans('commands.site.maintenance.messages.maintenance-off');
            }

            if ($modeMessage) {
                $io->newLine();
                $io->info($modeMessage, true);
                $io->newLine();
            }
        }
    }


    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'enableMaintenanceMode',
            ConsoleEvents::TERMINATE => 'disableMaintenanceMode',
        ];
    }
}
