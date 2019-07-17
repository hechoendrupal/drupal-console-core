<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\SaveStatisticsListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Drupal\Console\Core\Command\Chain\ChainCustomCommand;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\CountCodeLines;
use Drupal\Console\Core\Utils\TranslatorManagerInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class SaveStatisticsListener
 *
 * @package Drupal\Console\Core\EventSubscriber
 */
class SaveStatisticsListener implements EventSubscriberInterface
{

    /**
     * @var ShowGenerateChainListener
     */
    protected $countCodeLines;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var TranslatorManagerInterface
     */
    protected $translator;

    /**
     * FileSystem $fs
     */
    protected $fs;

    /**
     * SaveStatisticsListener constructor.
     *
     * @param CountCodeLines             $countCodeLines
     * @param ConfigurationManager       $configurationManager
     * @param TranslatorManagerInterface $translator
     */
    public function __construct(
        CountCodeLines $countCodeLines,
        ConfigurationManager $configurationManager,
        TranslatorManagerInterface $translator
    ) {
        $this->countCodeLines = $countCodeLines;
        $this->configurationManager = $configurationManager;
        $this->translator = $translator;

        $this->fs = new Filesystem();
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function saveStatistics(ConsoleTerminateEvent $event)
    {
        if ($event->getExitCode() != 0) {
            return;
        }

        $configGlobalAsArray = $this->configurationManager->getConfigGlobalAsArray();

        //Validate if the config is defined.
        if (is_null($configGlobalAsArray) || !isset($configGlobalAsArray['application']['statistics'])) {
            return;
        }

        //Validate if the statistics is enabled.
        if (!isset($configGlobalAsArray['application']['statistics']['enabled']) || !$configGlobalAsArray['application']['statistics']['enabled']) {
            return;
        }

        //Check that the namespace starts with 'Drupal\Console'.
        $class = new \ReflectionClass($event->getCommand());
        if (strpos($class->getNamespaceName(), "Drupal\Console") !== 0) {
            return;
        }

        //Validate if the command is not a custom chain command.
        if ($event->getCommand() instanceof ChainCustomCommand) {
            return;
        }

        $path =  $path = sprintf(
            '%s/.console/stats/',
            $this->configurationManager->getHomeDirectory()
        );

        $information = $event->getCommand()->getName() . ',' . $this->translator->getLanguage();

        $countCodeLines = $this->countCodeLines->getCountCodeLines();
        if ($countCodeLines > 0) {
            $information = $information . ',' . $countCodeLines;
        }

        try{
            $this->fs->appendToFile(
                $path .  date('Y-m-d') . '.csv',
                $information . PHP_EOL
            );
        }catch (\Exception $exception) {
            return;
        }
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'saveStatistics'];
    }
}
