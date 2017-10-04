<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Exec\ExecCommand.
 */

namespace Drupal\Console\Core\Command\Exec;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Drupal\Console\Core\Utils\ShellProcess;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Command;

/**
 * Class ExecCommand
 *
 * @package Drupal\Console\Core\Command\Exec
 */
class ExecCommand extends Command
{
    /**
     * @var ShellProcess
     */
    protected $shellProcess;

    /**
     * ExecCommand constructor.
     *
     * @param ShellProcess $shellProcess
     */
    public function __construct(ShellProcess $shellProcess)
    {
        $this->shellProcess = $shellProcess;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('exec')
            ->setDescription($this->trans('commands.exec.description'))
            ->addArgument(
                'bin',
                InputArgument::REQUIRED,
                $this->trans('commands.exec.arguments.bin')
            )->addOption(
                'working-directory',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.exec.options.working-directory')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $bin = $input->getArgument('bin');
        $workingDirectory = $input->getOption('working-directory');

        if (!$bin) {
            $io->error(
                $this->trans('commands.exec.messages.missing-bin')
            );

            return 1;
        }

        $name = $bin;
        if ($index = stripos($name, " ")) {
            $name = substr($name, 0, $index);
        }

        $finder = new ExecutableFinder();
        if (!$finder->find($name)) {
            $io->error(
                sprintf(
                    $this->trans('commands.exec.messages.binary-not-found'),
                    $name
                )
            );

            return 1;
        }

        if (!$this->shellProcess->exec($bin, $workingDirectory)) {
            $io->error(
                sprintf(
                    $this->trans('commands.exec.messages.invalid-bin')
                )
            );

            $io->writeln($this->shellProcess->getOutput());

            return 1;
        }

        $io->success(
            sprintf(
                $this->trans('commands.exec.messages.success'),
                $bin
            )
        );

        return 0;
    }
}
