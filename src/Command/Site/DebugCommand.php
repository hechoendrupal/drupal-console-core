<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Site\DebugCommand.
 */

namespace Drupal\Console\Core\Command\Site;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class SiteDebugCommand
 *
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

        $sites = array_keys($this->configurationManager->getSites());

        if (!$sites) {
            $io->error($this->trans('commands.site.debug.messages.invalid-sites'));

            return 1;
        }

        // --target argument
        $target = $input->getArgument('target');
        if (!$target) {
            $tableHeader =[
                $this->trans('commands.site.debug.messages.site'),
            ];

            $io->table($tableHeader, $sites);

            return 0;
        }

        $targetConfig = $this->configurationManager->readTarget($target);
        if (!$targetConfig) {
            $io->error($this->trans('commands.site.debug.messages.invalid-site'));

            return 1;
        }

        $io->info($target);
        $dumper = new Dumper();
        $io->writeln($dumper->dump($targetConfig, 2));

        return 0;
    }
}
