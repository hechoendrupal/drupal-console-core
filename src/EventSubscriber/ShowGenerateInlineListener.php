<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\ShowGenerateInlineListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ShowGenerateInlineListener
 *
 * @package Drupal\Console\Core\EventSubscriber
 */
class ShowGenerateInlineListener implements EventSubscriberInterface
{
    /**
     * @var TranslatorManagerInterface
     */
    protected $translator;

    /**
     * @var array
     */
    private $skipCommands = [
        'self-update',
        'list',
        'help',
        'drush'
    ];

    /**
     * @var array
     */
    private $skipOptions = [
        'env',
        'generate-inline',
        'generate-chain'
    ];

    /**
     * @var array
     */
    private $skipArguments = [
        'command',
        'command_name'
    ];

    /**
     * ShowGenerateInlineListener constructor.
     *
     * @param TranslatorManagerInterface $translator
     */
    public function __construct(
        TranslatorManagerInterface $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGenerateInline(ConsoleTerminateEvent $event)
    {
        if ($event->getExitCode() != 0) {
            return;
        }

        /* @var Command $command */
        $command = $event->getCommand();
        /* @var DrupalStyle $io */
        $io = new DrupalStyle($event->getInput(), $event->getOutput());

        $command_name = $command->getName();

        $this->skipArguments[] = $command_name;

        if (in_array($command->getName(), $this->skipCommands)) {
            return;
        }

        $input = $event->getInput();
        if ($input->getOption('generate-inline')) {
            $options = array_filter($input->getOptions());
            foreach ($this->skipOptions as $remove_option) {
                unset($options[$remove_option]);
            }

            $arguments = array_filter($input->getArguments());
            foreach ($this->skipArguments as $remove_argument) {
                unset($arguments[$remove_argument]);
            }

            $inline = '';
            foreach ($arguments as $argument_id => $argument) {
                if (is_array($argument)) {
                    $argument = implode(" ", $argument);
                } elseif (strstr($argument, ' ')) {
                    $argument = '"' . $argument . '"';
                }

                $inline .= " $argument";
            }

            // Refactor and remove nested levels. Then apply to arguments.
            foreach ($options as $optionName => $optionValue) {
                if (is_array($optionValue)) {
                    foreach ($optionValue as $optionItem) {
                        if (is_array($optionItem)) {
                            $inlineValue = implode(
                                ' ', array_map(
                                    function ($v, $k) {
                                        return $k . ':' . $v;
                                    },
                                    $optionItem,
                                    array_keys($optionItem)
                                )
                            );
                        } else {
                            $inlineValue = $optionItem;
                        }
                        $inline .= ' --' . $optionName . '="' . $inlineValue . '"';
                    }
                } else {
                    if (is_bool($optionValue)) {
                        $inline.= ' --' . $optionName;
                    } else {
                        $inline.= ' --' . $optionName . '="' . $optionValue . '"';
                    }
                }
            }

            // Print YML output and message
            $io->commentBlock(
                $this->translator->trans('application.messages.inline.generated')
            );

            $io->writeln(
                sprintf(
                    '$ drupal %s %s',
                    $command_name,
                    $inline
                )
            );
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGenerateInline'];
    }
}
