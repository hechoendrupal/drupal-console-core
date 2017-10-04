<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\CheckCommand.
 */

namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\RequirementChecker;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class CheckCommand
 *
 * @package Drupal\Console\Core\Command
 */
class CheckCommand extends Command
{
    /**
     * @var RequirementChecker
     */
    protected $requirementChecker;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * CheckCommand constructor.
     *
     * @param RequirementChecker   $requirementChecker
     * @param ChainQueue           $chainQueue
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        RequirementChecker $requirementChecker,
        ChainQueue $chainQueue,
        ConfigurationManager $configurationManager
    ) {
        $this->requirementChecker = $requirementChecker;
        $this->chainQueue = $chainQueue;
        $this->configurationManager = $configurationManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription($this->trans('commands.check.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $checks = $this->requirementChecker->getCheckResult();
        if (!$checks) {
            $phpCheckFile = $this->configurationManager->getHomeDirectory().'/.console/phpcheck.yml';

            if (!file_exists($phpCheckFile)) {
                $phpCheckFile =
                    $this->configurationManager->getApplicationDirectory().
                    DRUPAL_CONSOLE_CORE.
                    'config/dist/phpcheck.yml';
            }

            $io->newLine();
            $io->info($this->trans('commands.check.messages.file'));
            $io->comment($phpCheckFile);

            $checks = $this->requirementChecker->validate($phpCheckFile);
        }

        if (!$checks['php']['valid']) {
            $io->error(
                sprintf(
                    $this->trans('commands.check.messages.php-invalid'),
                    $checks['php']['current'],
                    $checks['php']['required']
                )
            );

            return 1;
        }

        if ($extensions = $checks['extensions']['required']['missing']) {
            foreach ($extensions as $extension) {
                $io->error(
                    sprintf(
                        $this->trans('commands.check.messages.extension-missing'),
                        $extension
                    )
                );
            }
        }

        if ($extensions = $checks['extensions']['recommended']['missing']) {
            foreach ($extensions as $extension) {
                $io->commentBlock(
                    sprintf(
                        $this->trans(
                            'commands.check.messages.extension-recommended'
                        ),
                        $extension
                    )
                );
            }
        }

        if ($configurations = $checks['configurations']['required']['missing']) {
            foreach ($configurations as $configuration) {
                $io->error(
                    sprintf(
                        $this->trans('commands.check.messages.configuration-missing'),
                        $configuration
                    )
                );
            }
        }

        if ($configurations = $checks['configurations']['required']['overwritten']) {
            foreach ($configurations as $configuration => $overwritten) {
                $io->commentBlock(
                    sprintf(
                        $this->trans(
                            'commands.check.messages.configuration-overwritten'
                        ),
                        $configuration,
                        $overwritten
                    )
                );
            }
        }

        if ($this->requirementChecker->isValid() && !$this->requirementChecker->isOverwritten()) {
            $io->success(
                $this->trans('commands.check.messages.success')
            );
        }

        return $this->requirementChecker->isValid();
    }
}
