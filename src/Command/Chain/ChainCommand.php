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
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\ChainDiscovery;
use Drupal\Console\Core\Command\Shared\InputTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ChainCommand
 *
 * @package Drupal\Console\Core\Command\Chain
 */
class ChainCommand extends Command
{
    use InputTrait;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var ChainDiscovery
     */
    protected $chainDiscovery;

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
        $this->chainDiscovery = $chainDiscovery;

        parent::__construct();
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
            )
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
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $file = $input->getOption('file');

        if (!$file) {
            $files = $this->chainDiscovery->getChainFiles(true);

            $file = $io->choice(
                $this->trans('commands.chain.questions.chain-file'),
                array_values($files)
            );
        }

        $file = calculateRealPath($file);
        $input->setOption('file', $file);

        $chainContent = $this->chainDiscovery->getFileContents($file);
        $inlinePlaceHolders = $this->chainDiscovery->extractInlinePlaceHolders($chainContent);

        $placeholder = $input->getOption('placeholder');
        if ($placeholder) {
            $placeholder = $this->placeHolderInlineValueAsArray($placeholder);
        }

        $placeholder = array_merge(
            array_filter(
                $inlinePlaceHolders,
                function($value) {
                    return $value !== null;
                }
            ),
            $placeholder
        );

        $inlinePlaceHolders = array_merge(
            $inlinePlaceHolders,
            $placeholder
        );

        $missingInlinePlaceHolders = array_diff_key(
            $inlinePlaceHolders,
            $placeholder
        );

        if ($missingInlinePlaceHolders) {
            foreach ($inlinePlaceHolders as $inlinePlaceHolder => $inlinePlaceHolderValue) {
                if (is_array($inlinePlaceHolderValue)) {
                    $placeholder[] = sprintf(
                        '%s:%s',
                        $inlinePlaceHolder,
                        $io->choice(
                            sprintf(
                                $this->trans('commands.chain.messages.select-value-for-placeholder'),
                                $inlinePlaceHolder
                            ),
                            $inlinePlaceHolderValue,
                            current($inlinePlaceHolderValue)
                        )
                    );
                } else {
                    $placeholder[] = sprintf(
                        '%s:%s',
                        $inlinePlaceHolder,
                        $io->ask(
                            sprintf(
                                $this->trans('commands.chain.messages.enter-value-for-placeholder'),
                                $inlinePlaceHolder
                            ),
                            $inlinePlaceHolderValue
                        )
                    );
                }
            }
            $input->setOption('placeholder', $placeholder);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $interactive = false;
        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;

        $file = $input->getOption('file');
        if (!$file) {
            $io->error($this->trans('commands.chain.messages.missing-file'));

            return 1;
        }

        $fileSystem = new Filesystem();
        $file = calculateRealPath($file);

        if (!$fileSystem->exists($file)) {
            $io->error(
                sprintf(
                    $this->trans('commands.chain.messages.invalid-file'),
                    $file
                )
            );

            return 1;
        }

        // Resolve inlinePlaceHolders
        $chainContent = $this->chainDiscovery->getFileContents($file);
        $inlinePlaceHolders = $this->chainDiscovery->extractInlinePlaceHolders($chainContent);

        foreach ($inlinePlaceHolders as $inlinePlaceHolder => $inlinePlaceHolderValue) {
            if (is_array($inlinePlaceHolderValue)) {
                $inlinePlaceHolders[$inlinePlaceHolder] = current($inlinePlaceHolderValue);
            }
        }

        $placeholder = $input->getOption('placeholder');
        if ($placeholder) {
            $placeholder = $this->placeHolderInlineValueAsArray($placeholder);
        }

        $placeholder = array_merge(
            array_filter(
                $inlinePlaceHolders,
                function($value) {
                    return $value !== null;
                }
            ),
            $placeholder
        );

        $inlinePlaceHolders = array_merge(
            $inlinePlaceHolders,
            $placeholder
        );

        $missingInlinePlaceHolders = array_diff_key(
            $inlinePlaceHolders,
            $placeholder
        );

        $missingInlinePlaceHoldersMessages = [];
        foreach ($missingInlinePlaceHolders as $inlinePlaceHolder => $inlinePlaceHolderValue) {
            $missingInlinePlaceHoldersMessages['default'][] = sprintf(
                '--placeholder="%s:%s_VALUE"',
                $inlinePlaceHolder,
                strtoupper($inlinePlaceHolder)
            );
            $missingInlinePlaceHoldersMessages['custom'][] = sprintf(
                '--%s="%s_VALUE"',
                $inlinePlaceHolder,
                strtoupper($inlinePlaceHolder)
            );
        }

        if ($missingInlinePlaceHolders) {
            $io->error(
                sprintf(
                    $this->trans('commands.chain.messages.missing-inline-placeholders'),
                    implode(', ', array_keys($missingInlinePlaceHolders))
                )
            );

            $io->info(
                $this->trans(
                    'commands.chain.messages.set-inline-placeholders'
                )
            );
            foreach ($missingInlinePlaceHoldersMessages['default'] as $missingInlinePlaceHoldersMessage) {
                $io->block($missingInlinePlaceHoldersMessage);
            }

            $io->info(
                $this->trans(
                    'commands.chain.messages.set-inline-placeholders'
                )
            );
            foreach ($missingInlinePlaceHoldersMessages['custom'] as $missingInlinePlaceHoldersMessage) {
                $io->block($missingInlinePlaceHoldersMessage);
            }

            return 1;
        }

        $inlinePlaceHolderData = new ArrayDataSource($placeholder);
        $placeholderResolver = new RegexPlaceholderResolver($inlinePlaceHolderData, '%{{', '}}');
        $chainContent = $placeholderResolver->resolvePlaceholder($chainContent);

        // Resolve environmentPlaceHolders
        $environmentPlaceHolders = $this->chainDiscovery->extractEnvironmentPlaceHolders($chainContent);
        $envPlaceHolderMap = [];
        $missingEnvironmentPlaceHolders = [];
        foreach ($environmentPlaceHolders as $envPlaceHolder) {
            if (!getenv($envPlaceHolder)) {
                $missingEnvironmentPlaceHolders[$envPlaceHolder] = sprintf(
                    'export %s=%s_VALUE',
                    $envPlaceHolder,
                    strtoupper($envPlaceHolder)
                );

                continue;
            }
            $envPlaceHolderMap[$envPlaceHolder] = getenv($envPlaceHolder);
        }

        if ($missingEnvironmentPlaceHolders) {
            $io->error(
                sprintf(
                    $this->trans('commands.chain.messages.missing-environment-placeholders-default'),
                    implode(', ', array_keys($missingEnvironmentPlaceHolders))
                )
            );

            $io->info($this->trans('commands.chain.messages.set-environment-placeholders-custom'));
            $io->block(array_values($missingEnvironmentPlaceHolders));

            return 1;
        }

        $envPlaceHolderData = new ArrayDataSource($envPlaceHolderMap);
        $placeholderResolver = new RegexPlaceholderResolver($envPlaceHolderData, '${{', '}}');
        $chainContent = $placeholderResolver->resolvePlaceholder($chainContent);

        $parser = new Parser();
        $configData = $parser->parse($chainContent);

        $commands = [];
        if (array_key_exists('commands', $configData)) {
            $commands = $configData['commands'];
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

            $io->text($command['command']);
            $io->newLine();

            $input = new ArrayInput($moduleInputs);
            if (!is_null($interactive)) {
                $input->setInteractive($interactive);
            }

            $allowFailure = array_key_exists('allow_failure', $command)?$command['allow_failure']:false;
            try {
                $callCommand->run($input, $io);
            } catch (\Exception $e) {
                if (!$allowFailure) {
                    $io->error($e->getMessage());
                    return 1;
                }
            }
        }

        return 0;
    }
}
