<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\ValidateDependenciesListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ValidateExecutionListener
 *
 * @package Drupal\Console\Core\EventSubscriber
 */
class ValidateExecutionListener implements EventSubscriberInterface
{
    /**
     * @var TranslatorManagerInterface
     */
    protected $translator;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * ValidateExecutionListener constructor.
     *
     * @param TranslatorManagerInterface $translator
     * @param ConfigurationManager       $configurationManager
     */
    public function __construct(
        TranslatorManagerInterface $translator,
        ConfigurationManager $configurationManager
    ) {
        $this->translator = $translator;
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function validateExecution(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();
        /* @var DrupalStyle $io */
        $io = new DrupalStyle($event->getInput(), $event->getOutput());

        $configuration = $this->configurationManager->getConfiguration();

        $mapping = $configuration->get('application.disable.commands')?:[];
        if (array_key_exists($command->getName(), $mapping)) {
            $extra = $mapping[$command->getName()];
            $message[] = sprintf(
                $this->translator->trans('application.messages.disable.command.error'),
                $command->getName()
            );
            if ($extra) {
                $message[] =  sprintf(
                    $this->translator->trans('application.messages.disable.command.extra'),
                    $extra
                );
            }
            $io->commentBlock($message);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'validateExecution'];
    }
}
