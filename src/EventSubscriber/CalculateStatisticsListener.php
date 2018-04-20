<?php

/**
 * @file
 * Contains \Drupal\Console\Core\EventSubscriber\CalculateStatisticsListener.
 */

namespace Drupal\Console\Core\EventSubscriber;

use Drupal\Console\Core\Utils\ConfigurationManager;
use GuzzleHttp\Client;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class CalculateStatisticsListener
 *
 * @package Drupal\Console\Core\EventSubscriber
 */
class CalculateStatisticsListener implements EventSubscriberInterface
{

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * FileSystem $fs
     */
    protected $fs;

    /**
     * SaveStatisticsListener constructor.
     *
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        ConfigurationManager $configurationManager
    ) {
        $this->configurationManager = $configurationManager;
        $this->fs = new Filesystem();
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function calculateStatistics(ConsoleTerminateEvent $event)
    {
        if ($event->getExitCode() != 0) {
            return;
        }

        if (!$this->configurationManager->getConfiguration()->get('application.share.statistics')) {
            return;
        }

        $path = $this->configurationManager->getConsoleDirectory() . 'stats';

        $finder = new Finder();
        $finder
            ->files()
            ->name('*-pending.csv')
            ->notName(date('Y_m_d').'-pending.csv')
            ->in($path);

        $statistics_keys = ['command', 'language', 'linesOfCode'];
        $commands = [];
        $languages = [];

        foreach ($finder as $file) {
            $file_content = array_filter(explode("\n", file_get_contents($file->getPathname())));
            foreach ($file_content as $value) {
                $content = explode(";", $value);

                if (count($content) === 2) {
                    array_push($content, 0);
                }

                $commands = $this->getCommandStatisticsAsArray($commands, array_combine($statistics_keys, $content));
                $languages = $this->getLanguageStatisticsAsArray($languages, array_combine($statistics_keys, $content));
            }

            $this->fs->rename($file->getPathname(), str_replace('pending', 'send', $file->getPathname()));
        }

        $client = new Client();

        $client->post(
            'http://127.0.0.1:8088/statistics?_format=json',
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Basic YWRtaW46YWRtaW4='
                ],
                'json' => ['commands' => $commands, 'languages' => $languages]
            ]
        );
    }

    /**
     * Build the statistics by command.
     *
     * @param  $commands
     * @param  $content
     * @return array
     */
    private function getCommandStatisticsAsArray($commands, $content)
    {
        $executed = $commands[$content['command']]['executed'] + 1;
        $linesOfCode = $commands[$content['command']]['linesOfCode'] + $content['linesOfCode'];

        $commands[$content['command']] = ["executed" => $executed, "linesOfCode" => $linesOfCode];

        return $commands;
    }

    /**
     * Update the languages by command.
     *
     * @param  $languages
     * @param  $content
     * @return array
     */
    private function getLanguageStatisticsAsArray($languages, $content)
    {
        $languages[$content['language']] = $languages[$content['language']] + 1;

        return $languages;
    }

    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::TERMINATE => 'calculateStatistics'];
    }
}
