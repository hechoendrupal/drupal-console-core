<?php
namespace Drupal\Console\Core\Utils;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\ExecutableFinder;
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
     * Finds an executable by name.
     *
     * Uses the ExecutableFinder Symfony component, adding the
     * [appRoot]/vendor/bin directory to the searchable ones for the case where
     * the local bin is not set in the PATH environment variable.
     *
     * @param string      $name      The executable name (without the
     *                               extension).
     * @param string|null $default   The default to return if no executable is
     *                               found.
     * @param array       $extraDirs Additional dirs to check into.
     *
     * @return string|null The executable path or default value
     */
    public function findExecutable($name, $default = null, array $extraDirs = [])
    {
        $finder = new ExecutableFinder();
        $extraDirs[] = $this->appRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin';
        return $finder->find($name, $default, $extraDirs);
    }

    /**
     * @param string $command
     * @param string $workingDirectory
     *
     * @throws ProcessFailedException
     *
     * @return Process
     */
    public function exec($command, $workingDirectory = null)
    {
        if (!$workingDirectory || $workingDirectory === '') {
            $workingDirectory = $this->appRoot;
        }

        // Prepare the process.
        $this->process = new Process($command);
        $this->process->setWorkingDirectory($workingDirectory);
        $this->process->enableOutput();
        $this->process->setTimeout(null);

        // Inform about context.
        if (realpath($workingDirectory)) {
            $this->io->comment(
                $this->translator->trans('commands.exec.messages.working-directory') . ': ',
                false
            );
            $this->io->writeln(realpath($workingDirectory));
        }
        $this->io->comment(
            $this->translator->trans('commands.exec.messages.executing-command') . ': ',
            false
        );
        $this->io->writeln(is_array($command) ? implode(' ', $command) : $command);

        // Run the process.
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
     * Executes a subprocess in TTY mode, if possible.
     *
     * @param string $command
     * @param bool $fallbackAllowed
     *   (Optional) If TTY is not possible, and this is set to TRUE, falls back
     *   to running as a non-interactive process, calling ::exec().
     * @param string $workingDirectory
     *
     * @return bool
     *   TRUE if the process run successfully, FALSE otherwise.
     */
    public function execTty($command, $fallbackAllowed = false, $workingDirectory = null)
    {
        if (!$workingDirectory || $workingDirectory === '') {
            $workingDirectory = $this->appRoot;
        }

        // Prepare the process.
        $this->process = new Process($command);
        $this->process->setWorkingDirectory($workingDirectory);
        $this->process->setTimeout(null);

        // Try running the process as a TTY one, i.e. allowing user
        // interaction.
        try {
            $this->process->setTty(true);
        } catch(\RuntimeException $e) {
            // If fallback is allowed then try to run as a non-interactive
            // process.
            if ($fallbackAllowed) {
                return $this->exec($command, $workingDirectory);
            } else {
                $this->getIo()->error($e->getMessage());
            }
        }

        // Inform about context.
        if (realpath($workingDirectory)) {
            $this->io->comment(
                $this->translator->trans('commands.exec.messages.working-directory') . ': ',
                false
            );
            $this->io->writeln(realpath($workingDirectory));
        }
        $this->io->comment(
            $this->translator->trans('commands.exec.messages.executing-command') . ': ',
            false
        );
        $this->io->writeln(is_array($command) ? implode(' ', $command) : $command);

        // Run the process.
        $this->process->run();
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
