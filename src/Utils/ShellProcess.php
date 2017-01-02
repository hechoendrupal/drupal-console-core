<?php
namespace Drupal\Console\Core\Utils;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ShellProcess
 *
 * @package Drupal\Console\Core\Utils
 */
class ShellProcess
{
    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var TranslatorManagerInterface
     */
    protected $translator;

    /**
     * @var ShellProcess
     */
    private $process;

    /**
     * @var DrupalStyle
     */
    private $io;

    /**
     * Process constructor.
     *
     * @param string                     $appRoot
     * @param TranslatorManagerInterface $translator
     */
    public function __construct($appRoot, $translator)
    {
        $this->appRoot = $appRoot;
        $this->translator = $translator;

        $output = new ConsoleOutput();
        $input = new ArrayInput([]);
        $this->io = new DrupalStyle($input, $output);
    }

    /**
     * @param string $command
     * @param string $workingDirectory
     *
     * @throws ProcessFailedException
     *
     * @return Process
     */
    public function exec($command, $workingDirectory=null)
    {
        if (!$workingDirectory || $workingDirectory==='') {
            $workingDirectory = $this->appRoot;
        }

        $this->io->newLine();
        $this->io->comment(
            $this->translator->trans('commands.exec.messages.working-directory') .': ',
            false
        );
        $this->io->writeln($workingDirectory);
        $this->io->comment(
            $this->translator->trans('commands.exec.messages.executing-command') .': ',
            false
        );
        $this->io->writeln($command);

        $this->process = new Process($command);
        $this->process->setWorkingDirectory($workingDirectory);
        $this->process->enableOutput();
        $this->process->setTimeout(null);
        $this->process->run(
            function ($type, $buffer) {
                $this->io->write($buffer);
            }
        );

        if (!$this->process->isSuccessful()) {
            throw new ProcessFailedException($this->process);
        }

        return $this->process->isSuccessful();
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->process->getOutput();
    }
}
