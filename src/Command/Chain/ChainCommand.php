<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Chain\ChainCommand.
 */

namespace Drupal\Console\Core\Command\Chain;

use Dflydev\PlaceholderResolver\DataSource\ArrayDataSource;
use Dflydev\PlaceholderResolver\RegexPlaceholderResolver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\ChainDiscovery;
use Drupal\Console\Core\Command\Shared\InputTrait;

/**
 * Class ChainCommand
 *
 * @package Drupal\Console\Core\Command\Chain
 */
class ChainCommand extends BaseCommand
{
    use InputTrait;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * ChainCommand constructor.
     *
     * @param ChainQueue     $chainQueue
     * @param ChainDiscovery $chainDiscovery
     */
    public function __construct(
        ChainQueue $chainQueue,
        ChainDiscovery $chainDiscovery
    ) {
        $this->chainQueue = $chainQueue;

        parent::__construct($chainDiscovery);
        $this->ignoreValidationErrors();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('chain')
            ->setDescription($this->trans('commands.chain.description'))
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.chain.options.file')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $file = $this->getFileOption();

        $chainContent = $this->chainDiscovery
            ->parseContent(
                $file,
                $this->getOptionsAsArray()
            );

        $inlinePlaceHolders = $this->chainDiscovery
            ->extractInlinePlaceHolders($chainContent);

        foreach ($inlinePlaceHolders as $inlinePlaceHolder => $inlinePlaceHolderValue) {
            if (is_array($inlinePlaceHolderValue)) {
                $placeHolderValue = $this->getIo()->choice(
                    sprintf(
                        $this->trans('commands.chain.messages.select-value-for-placeholder'),
                        $inlinePlaceHolder
                    ),
                    $inlinePlaceHolderValue,
                    current($inlinePlaceHolderValue)
                );
            } else {
                $placeHolderValue = $this->getIo()->ask(
                    sprintf(
                        $this->trans(
                            'commands.chain.messages.enter-value-for-placeholder'
                        ),
                        $inlinePlaceHolder
                    ),
                    $inlinePlaceHolderValue
                );
            }

            if (!$input->hasOption($inlinePlaceHolder)) {
                $this->addOption(
                    $inlinePlaceHolder,
                    null,
                    InputOption::VALUE_OPTIONAL,
                    null,
                    null
                );
            }

            $input->setOption($inlinePlaceHolder, $placeHolderValue);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $interactive = false;

        $file = $input->getOption('file');
        if (!$file) {
            $this->getIo()->error($this->trans('commands.chain.messages.missing-file'));

            return 1;
        }

        $fileSystem = new Filesystem();
        $file = calculateRealPath($file);

        if (!$fileSystem->exists($file)) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.chain.messages.invalid-file'),
                    $file
                )
            );

            return 1;
        }

        $chainContent = $this->chainDiscovery
            ->parseContent(
                $file,
                $this->getOptionsAsArray()
            );

        // Resolve inlinePlaceHolders
        $inlinePlaceHolders = $this->chainDiscovery
            ->extractInlinePlaceHolders($chainContent);

        if ($inlinePlaceHolders) {
            $this->getIo()->error(
                sprintf(
                    $this->trans('commands.chain.messages.missing-inline-placeholders'),
                    implode(', ', array_keys($inlinePlaceHolders))
                )
            );

            $this->getIo()->info(
                $this->trans(
                    'commands.chain.messages.set-inline-placeholders'
                )
            );

            foreach ($inlinePlaceHolders as $inlinePlaceHolder => $inlinePlaceHolderValue) {
                $missingInlinePlaceHoldersMessage = sprintf(
                    '--%s="%s_VALUE"',
                    $inlinePlaceHolder,
                    strtoupper($inlinePlaceHolder)
                );

                $this->getIo()->block($missingInlinePlaceHoldersMessage);
            }

            return 1;
        }

        // Resolve environmentPlaceHolders
        $environmentPlaceHolders = $this->chainDiscovery
            ->extractEnvironmentPlaceHolders($chainContent);
        if ($environmentPlaceHolders) {
            $this->getIo()->error(
                sprintf(
                    $this->trans(
                        'commands.chain.messages.missing-environment-placeholders'
                    ),
                    implode(
                        ', ',
                        array_values($environmentPlaceHolders)
                    )
                )
            );

            $this->getIo()->info(
                $this->trans(
                    'commands.chain.messages.set-environment-placeholders'
                )
            );

            foreach ($environmentPlaceHolders as $envPlaceHolder) {
                $missingEnvironmentPlaceHoldersMessage = sprintf(
                    'export %s=%s_VALUE',
                    $envPlaceHolder,
                    strtoupper($envPlaceHolder)
                );

                $this->getIo()->block($missingEnvironmentPlaceHoldersMessage);
            }

            return 1;
        }

        $parser = new Parser();
        $chainData = $parser->parse($chainContent);

        $commands = [];
        if (array_key_exists('commands', $chainData)) {
            $commands = $chainData['commands'];
        }

        $chainInlineOptions = $input->getOptions();
        unset($chainInlineOptions['file']);

        foreach ($commands as $command) {
            $moduleInputs = [];
            $arguments = !empty($command['arguments']) ? $command['arguments'] : [];
            $options = !empty($command['options']) ? $command['options'] : [];

            foreach ($arguments as $key => $value) {
                $moduleInputs[$key] = is_null($value) ? '' : $value;
            }

            foreach ($options as $key => $value) {
                $moduleInputs['--'.$key] = is_null($value) ? '' : $value;
            }

            // Get application global options
            foreach ($this->getApplication()->getDefinition()->getOptions() as $option) {
                $optionName = $option->getName();
                if (array_key_exists($optionName, $chainInlineOptions)) {
                    $optionValue = $chainInlineOptions[$optionName];
                    // Set global option only if is not available in command options
                    if (!isset($moduleInputs['--' . $optionName]) && $optionValue) {
                        $moduleInputs['--' . $optionName] = $optionValue;
                    }
                }
            }

            $application = $this->getApplication();
            $callCommand = $application->find($command['command']);

            if (!$callCommand) {
                continue;
            }

            $this->getIo()->text($command['command']);
            $this->getIo()->newLine();

            $input = new ArrayInput($moduleInputs);
            if (!is_null($interactive)) {
                $input->setInteractive($interactive);
            }

            $allowFailure = array_key_exists('allow_failure', $command)?$command['allow_failure']:false;
            try {
                $callCommand->run($input, $this->getIo());
            } catch (\Exception $e) {
                if (!$allowFailure) {
                    $this->getIo()->error($e->getMessage());
                    return 1;
                }
            }
        }

        return 0;
    }
}
