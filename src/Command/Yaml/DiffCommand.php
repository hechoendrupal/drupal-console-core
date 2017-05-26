<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Yaml\DiffCommand.
 */

namespace Drupal\Console\Core\Command\Yaml;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\NestedArray;

class DiffCommand extends Command
{
    use CommandTrait;

    /**
     * @var NestedArray
     */
    protected $nestedArray;

    /**
     * DiffCommand constructor.
     *
     * @param NestedArray $nestedArray
     */
    public function __construct(NestedArray $nestedArray)
    {
        $this->nestedArray = $nestedArray;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('yaml:diff')
            ->setDescription($this->trans('commands.yaml.diff.description'))
            ->addArgument(
                'yaml-left',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.diff.arguments.yaml-left')
            )
            ->addArgument(
                'yaml-right',
                InputArgument::REQUIRED,
                $this->trans('commands.yaml.diff.arguments.yaml-right')
            )
            ->addOption(
                'stats',
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.yaml.diff.options.stats')
            )
            ->addOption(
                'negate',
                false,
                InputOption::VALUE_NONE,
                $this->trans('commands.yaml.diff.options.negate')
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.yaml.diff.options.limit')
            )
            ->addOption(
                'offset',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.yaml.diff.options.offset')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $yaml = new Parser();

        $yaml_left = $input->getArgument('yaml-left');
        $yaml_right = $input->getArgument('yaml-right');

        $stats = $input->getOption('stats');

        $negate = $input->getOption('negate');

        $limit = $input->getOption('limit');
        $offset = $input->getOption('offset');

        if ($negate == 1 || $negate == 'TRUE') {
            $negate = true;
        } else {
            $negate = false;
        }

        try {
            $yamlLeftParsed = $yaml->parse(file_get_contents($yaml_left));

            if (empty($yamlLeftParsed)) {
                $io->error(
                    sprintf(
                        $this->trans('commands.yaml.merge.messages.wrong-parse'),
                        $yaml_left
                    )
                );
            }

            $yamlRightParsed = $yaml->parse(file_get_contents($yaml_right));

            if (empty($yamlRightParsed)) {
                $io->error(
                    sprintf(
                        $this->trans('commands.yaml.merge.messages.wrong-parse'),
                        $yaml_right
                    )
                );
            }
        } catch (\Exception $e) {
            $io->error($this->trans('commands.yaml.merge.messages.error-parsing').': '.$e->getMessage());

            return;
        }

        $statistics = ['total' => 0, 'equal'=> 0 , 'diff' => 0];
        $diff = $this->nestedArray->arrayDiff($yamlLeftParsed, $yamlRightParsed, $negate, $statistics);
        print_r($diff);

        if ($stats) {
            $io->info(
                sprintf(
                    $this->trans('commands.yaml.diff.messages.total'),
                    $statistics['total']
                )
            );

            $io->info(
                sprintf(
                    $this->trans('commands.yaml.diff.messages.diff'),
                    $statistics['diff']
                )
            );

            $io->info(
                sprintf(
                    $this->trans('commands.yaml.diff.messages.equal'),
                    $statistics['equal']
                )
            );

            return;
        }
        // FLAT YAML file to display full yaml to be used with command yaml:update:key or yaml:update:value
        $diffFlatten = [];
        $keyFlatten = '';
        $this->nestedArray->yamlFlattenArray($diff, $diffFlatten, $keyFlatten);

        if ($limit !== null) {
            if (!$offset) {
                $offset = 0;
            }
            $diffFlatten = array_slice($diffFlatten, $offset, $limit);
        }

        $tableHeader = [
            $this->trans('commands.yaml.diff.messages.key'),
            $this->trans('commands.yaml.diff.messages.value'),
        ];

        $tableRows = [];
        foreach ($diffFlatten as $yamlKey => $yamlValue) {
            $tableRows[] = [
                $yamlKey,
                $yamlValue
            ];
            print $yamlKey . "\n";
            print $yamlValue . "\n";
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $validator_filename = function ($value) use ($io) {
            if (!strlen(trim($value)) || !is_file($value)) {
                $io->error($this->trans('commands.common.errors.invalid-file-path'));

                return false;
            }

            return $value;
        };

        // --yaml-left option
        $yaml_left = $input->getArgument('yaml-left');
        if (!$yaml_left) {
            while (true) {
                $yaml_left = $io->ask(
                    $this->trans('commands.yaml.diff.questions.yaml-left'),
                    null,
                    $validator_filename
                );

                if ($yaml_left) {
                    break;
                }
            }

            $input->setArgument('yaml-left', $yaml_left);
        }

        // --yaml-right option
        $yaml_right = $input->getArgument('yaml-right');
        if (!$yaml_right) {
            while (true) {
                $yaml_right = $io->ask(
                    $this->trans('commands.yaml.diff.questions.yaml-right'),
                    null,
                    $validator_filename
                );

                if ($yaml_right) {
                    break;
                }
            }

            $input->setArgument('yaml-right', $yaml_right);
        }
    }
}
