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
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Command\Shared\InputTrait;

/**
 * Class ChainCustomCommand
 *
 * @package Drupal\Console\Core\Command\ChainRegister
 */
class ChainCustomCommand extends Command
{
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
     * @var array
     */
    protected $placeHolders;

    /**
     * @var string
     */
    protected $file;

    /**
     * ChainRegister constructor.
     *
     * @param $name
     * @param $description
     * @param $placeHolders
     * @param $file
     */
    public function __construct(
        $name,
        $description,
        $placeHolders,
        $file)
    {
        $this->name = $name;
        $this->description = $description;
        $this->file = $file;
        $this->placeHolders = $placeHolders;

        parent::__construct();
        foreach ($placeHolders['inline'] as $placeHolderName => $placeHolderValue) {
            $this->addOption(
                $placeHolderName,
                null,
                InputOption::VALUE_OPTIONAL,
                $placeHolderName,
                null
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription($this->description);
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

        $placeholder = [];
        foreach ($input->getOptions() as $option => $value) {
            if ($value) {
                if (is_bool($value)) {
                    $value = true;
                }
                if (array_key_exists($option, $this->placeHolders['inline'])) {
                    $placeholder[] = $option.':'.$value;
                } else {
                    $arguments['--' . $option] = $value;
                }
            }
        }

        if ($placeholder) {
            $arguments['--placeholder'] = $placeholder;
        }

        $commandInput = new ArrayInput($arguments);
        if (array_key_exists('--no-interaction', $arguments)) {
            $commandInput->setInteractive(false);
        }

        return $command->run($commandInput, $output);
    }
}
