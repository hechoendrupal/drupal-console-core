<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Debug\SiteCommand.
 */

namespace Drupal\Console\Core\Command\Debug;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class SiteCommand
 *
 * @package Drupal\Console\Core\Command\Debug
 */
class SiteCommand extends Command
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * SiteCommand constructor.
     *
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
            ->setName('debug:site')
            ->setDescription($this->trans('commands.debug.site.description'))
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.site.options.target'),
                null
            )
            ->addArgument(
                'property',
                InputArgument::OPTIONAL,
                $this->trans('commands.debug.site.options.property'),
                null
            )
            ->setHelp($this->trans('commands.debug.site.help'))
            ->setAliases(['dsi']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $sites = array_keys($this->configurationManager->getSites());

        if (!$sites) {
            $io->error($this->trans('commands.debug.site.messages.invalid-sites'));

            return 1;
        }


        // --target argument
        $target = $input->getArgument('target');
        if (!$target) {
            $tableHeader =[
                $this->trans('commands.debug.site.messages.site'),
            ];

            $io->table($tableHeader, $sites);

            return 0;
        }

        $targetConfig = $this->configurationManager->readTarget($target);
        if (!$targetConfig) {
            $io->error($this->trans('commands.debug.site.messages.invalid-site'));

            return 1;
        }

        // --property argument, allows the user to fetch specific properties of the selected site
        $property = $input->getArgument('property');
        if ($property) {
            $property_keys = explode('.', $property);

            $val = $targetConfig;
            foreach ($property_keys as $property_key) {
                $val = &$val[$property_key];
            }

            $io->writeln($val);
            return 0;
        }

        $io->info($target);
        $dumper = new Dumper();
        $io->writeln($dumper->dump($targetConfig, 2));

        return 0;
    }
}
