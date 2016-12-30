<?php

/**
 * @file
 * Contains Drupal\Console\Core\Command\ChainCustomCommand.
 */

namespace Drupal\Console\Core\Command\Chain;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Command\Shared\InputTrait;

/**
 * Class ChainCustomCommand
 *
 * @package Drupal\Console\Core\Command\ChainRegister
 */
class ChainCustomCommand extends Command
{
    use CommandTrait;
    use InputTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $file;

    /**
     * ChainRegister constructor.
     *
     * @param $name
     * @param $description
     * @param $file
     */
    public function __construct($name, $description, $file)
    {
        $this->name = $name;
        $this->description = $description;
        $this->file = $file;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description)
            ->addOption(
                'placeholder',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                $this->trans('commands.chain.options.placeholder')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('chain');

        $arguments = [
            'command' => 'chain',
            '--file'  => $this->file,
        ];

        if ($placeholder = $input->getOption('placeholder')) {
            $arguments['--placeholder'] = $this->inlineValueAsArray($placeholder);
        }

        foreach ($input->getOptions() as $option => $value) {
            if ($option != 'placeholder' && $value) {
                if (is_bool($value)) {
                    $value = true;
                }
                $arguments['--'.$option] = $value;
            }
        }

        $commandInput = new ArrayInput($arguments);
        if (array_key_exists('--no-interaction', $arguments)) {
            $commandInput->setInteractive(false);
        }

        return $command->run($commandInput, $output);
    }
}
