<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\DefaultValueEventListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;

/**
 * Class DefaultValueEventListener
 *
 * @package Drupal\Console\Core\EventSubscriber
 */
class DefaultValueEventListener implements EventSubscriberInterface
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    private $skipCommands = [
        'self-update',
        'list',
        'chain',
        'drush'
    ];

    /**
     * DefaultValueEventListener constructor.
     *
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        ConfigurationManager $configurationManager
    ) {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function setDefaultValues(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();
        $configuration = $this->configurationManager
            ->getConfiguration();

        if (in_array($command->getName(), $this->skipCommands)) {
            return;
        }

        $input = $command->getDefinition();
        $options = $input->getOptions();
        foreach ($options as $key => $option) {
            $defaultOption = sprintf(
                'application.default.commands.%s.options.%s',
                str_replace(':', '.', $command->getName()),
                $key
            );
            $defaultValue = $configuration->get($defaultOption);
            if ($defaultValue) {
                $option->setDefault($defaultValue);
            }
        }

        $arguments = $input->getArguments();
        foreach ($arguments as $key => $argument) {
            $defaultArgument = sprintf(
                'application.default.commands.%s.arguments.%s',
                str_replace(':', '.', $command->getName()),
                $key
            );
            $defaultValue = $configuration->get($defaultArgument);
            if ($defaultValue) {
                $argument->setDefault($defaultValue);
            }
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'setDefaultValues'];
    }
}
