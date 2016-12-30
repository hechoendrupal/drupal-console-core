<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Site\DebugCommand.
 */

namespace Drupal\Console\Core\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class SiteDebugCommand
 * @package Drupal\Console\Core\Command\Site
 */
class DebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * DebugCommand constructor.
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        ConfigurationManager $configurationManager
    ) {
        $this->configurationManager = $configurationManager;
        parent::__construct();
    }

    /**
     * @{@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('site:debug')
            ->setDescription($this->trans('commands.site.debug.description'))
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                $this->trans('commands.site.debug.options.target'),
                null
            )
            ->setHelp($this->trans('commands.site.debug.help'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $sitesDirectory =  $this->configurationManager->getSitesDirectory();

        if (!is_dir($sitesDirectory)) {
            $io->error(
                sprintf(
                    $this->trans('commands.site.debug.messages.directory-not-found'),
                    $sitesDirectory
                )
            );

            return 1;
        }

        // --target argument
        $target = $input->getArgument('target');
        if ($target) {
            $io->write(
                $this->siteDetail($target)
            );

            return 0;
        }

        $tableHeader =[
            $this->trans('commands.site.debug.messages.site'),
            $this->trans('commands.site.debug.messages.host'),
            $this->trans('commands.site.debug.messages.root')
        ];

        $tableRows = $this->siteList($sitesDirectory);

        $io->table($tableHeader, $tableRows);
        return 0;
    }

    /**
     * @param string $target
     *
     * @return string
     */
    private function siteDetail($target)
    {
        if ($targetConfig = $this->configurationManager->readTarget($target)) {
            $dumper = new Dumper();

            return $dumper->dump($targetConfig, 2);
        }

        return null;
    }

    /**
     * @param string $sitesDirectory
     * @return array
     */
    private function siteList($sitesDirectory)
    {
        $finder = new Finder();
        $finder->in($sitesDirectory);
        $finder->name("*.yml");

        $tableRows = [];
        foreach ($finder as $site) {
            $siteName = $site->getBasename('.yml');
            $environments = $this->configurationManager
                ->readSite($site->getRealPath());

            if (!$environments || !is_array($environments)) {
                continue;
            }

            foreach ($environments as $environment => $config) {
                $tableRows[] = [
                    $siteName . '.' . $environment,
                    array_key_exists('host', $config) ? $config['host'] : 'local',
                    array_key_exists('root', $config) ? $config['root'] : ''
                ];
            }
        }

        return $tableRows;
    }
}
