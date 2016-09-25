<?php

/**
 * @file
 * Contains \Drupal\Console\EventSubscriber\ShowGenerateChainListener.
 */

namespace Drupal\Console\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Utils\TranslatorManager;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class ShowGenerateChainListener
 * @package Drupal\Console\EventSubscriber
 */
class ShowGenerateChainListener implements EventSubscriberInterface
{
    /**
     * @var TranslatorManager
     */
    protected $translator;

    private $skipCommands = [
        'self-update',
        'list',
        'help',
        'drush'
    ];

    private $skipOptions = [
        'env',
        'generate-inline',
        'generate-chain'
    ];

    private $skipArguments = [
        'command',
        'command_name'
    ];

    /**
     * ShowGenerateChainListener constructor.
     * @param TranslatorManager $translator
     */
    public function __construct(
        TranslatorManager $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function showGenerateChain(ConsoleTerminateEvent $event)
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

        if ($input->getOption('generate-chain')) {
            $commands = [];
            $options = array_filter($input->getOptions());
            foreach ($this->skipOptions as $remove_option) {
                unset($options[$remove_option]);
            }

            $arguments = array_filter($input->getArguments());
            foreach ($this->skipArguments as $remove_argument) {
                unset($arguments[$remove_argument]);
            }

            $commands['commands'][0]['command'] = $command_name;

            if ($options) {
                $commands['commands'][0]['options'] = $options;
            }

            if ($arguments) {
                $commands['commands'][0]['arguments'] = $arguments;
            }

            $dumper = new Dumper();
            $yml = $dumper->dump($commands, 10);

            $yml = str_replace(
                sprintf('\'%s\':', $command_name),
                sprintf('  - command: %s', $command_name),
                $yml
            );

            $io->commentBlock(
                $this->translator->trans('application.messages.chain.generated')
            );

            $io->writeln($yml);
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'showGenerateChain'];
    }
}
