<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\HelpCommand.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Helper\DescriptorHelper;

/**
 * HelpCommand displays the help for a given command.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HelpCommand extends Command
{
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('help')
            ->setDefinition($this->createDefinition())
            ->setDescription($this->trans('commands.help.description'))
            ->setHelp($this->trans('commands.help.help'));
    }

    /**
     * Sets the command.
     *
     * @param $command
     *  The command to set
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (null === $this->command) {
            $this->command = $this->getApplication()->find($input->getArgument('command_name'));
        }

        if ($input->getOption('xml')) {
            $this->getIo()->info($this->trans('commands.help.messages.deprecated'), E_USER_DEPRECATED);
            $input->setOption('format', 'xml');
        }

        $helper = new DescriptorHelper();
        $helper->describe(
            $this->getIo(),
            $this->command,
            [
                'format' => $input->getOption('format'),
                'raw_text' => $input->getOption('raw'),
                'command_name' => $input->getArgument('command_name'),
                'translator' => $this->getApplication()->getTranslator()
            ]
        );

        $this->command = null;
        $this->getIo()->newLine();
    }

    /**
     * {@inheritdoc}
     */
    private function createDefinition()
    {
        return new InputDefinition(
            [
            new InputArgument('command_name', InputArgument::OPTIONAL, $this->trans('commands.help.arguments.command-name'), 'help'),
            new InputOption('xml', null, InputOption::VALUE_NONE, $this->trans('commands.help.options.xml')),
            new InputOption('raw', null, InputOption::VALUE_NONE, $this->trans('commands.help.options.raw')),
            new InputOption('format', null, InputOption::VALUE_REQUIRED, $this->trans('commands.help.options.format'), 'txt'),
            ]
        );
    }
}
