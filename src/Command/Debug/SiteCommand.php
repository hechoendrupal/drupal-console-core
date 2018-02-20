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
        $sites = $this->configurationManager->getSites();

        if (!$sites) {
            $this->getIo()->warning($this->trans('commands.debug.site.messages.invalid-sites'));

            return 0;
        }

        $target = $input->getArgument('target');
        if (!$target) {
            foreach ($sites as $key => $site) {
                $environments = array_keys($site);
                unset($environments[0]);

                $environments = array_map(
                    function ($element) use ($key) {
                        return $key . '.' . $element;
                    },
                    $environments
                );

                $this->getIo()->info($key);
                $this->getIo()->listing($environments);
            }

            return 0;
        }

        $targetConfig = $this->configurationManager->readTarget($target);
        if (!$targetConfig) {
            $this->getIo()->error($this->trans('commands.debug.site.messages.invalid-site'));

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

            $this->getIo()->writeln($val);
            return 0;
        }

        $this->getIo()->info($target);
        $dumper = new Dumper();
        $this->getIo()->writeln($dumper->dump($targetConfig, 4, 2));

        return 0;
    }
}
