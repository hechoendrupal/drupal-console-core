<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\AboutCommand.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class AboutCommand extends Command
{
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription($this->trans('commands.about.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $application = $this->getApplication();

        $aboutTitle = sprintf(
            '%s (%s)',
            $application->getName(),
            $application->getVersion()
        );

        $io->setDecorated(false);
        $io->title($aboutTitle);
        $io->setDecorated(true);

        $commands = [
            'init' => [
                $this->trans('commands.init.description'),
                'drupal init'
            ],
            //            'quick-start' => [
            //                $this->trans('commands.common.messages.quick-start'),
            //                'drupal quick:start'
            //            ],
            //            'site-new' => [
            //                $this->trans('commands.site.new.description'),
            //                'drupal site:new'
            //            ],
            'site-install' => [
                $this->trans('commands.site.install.description'),
                sprintf(
                    'drupal site:install'
                )
            ],
            'list' => [
                $this->trans('commands.list.description'),
                'drupal list',
            ]
        ];

        foreach ($commands as $command => $commandInfo) {
            $io->writeln($commandInfo[0]);
            $io->newLine();
            $io->comment(sprintf('  %s', $commandInfo[1]));
            $io->newLine();
        }

        $io->setDecorated(false);
        $io->section($this->trans('commands.self-update.description'));
        $io->setDecorated(true);
        $io->comment('  drupal self-update');
        $io->newLine();
    }
}
