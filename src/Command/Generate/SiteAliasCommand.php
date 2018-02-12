<?php

namespace Drupal\Console\Core\Command\Generate;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\DrupalFinder;
use Drupal\Console\Core\Generator\SiteAliasGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SiteAliasCommand
 *
 * @package Drupal\Console\Command\Generate
 */
class SiteAliasCommand extends Command
{
    /**
     * @var SiteAliasGenerator
     */
    protected $generator;

    /**
     * @var DrupalFinder
     */
    protected $drupalFinder;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    private $types = [
        'local',
        'ssh',
        'container'
    ];

    /**
     * @var array
     */
    private $extraOptions = [
        'ssh' => [
            'none' => '',
            'vagrant' => '-o PasswordAuthentication=no -i ~/.vagrant.d/insecure_private_key',
        ],
        'container' => [
            'none' => '',
            'drupal4docker' => 'docker-compose exec --user=82 php'
        ]
    ];

    public function __construct(
        SiteAliasGenerator $generator,
        ConfigurationManager $configurationManager,
        DrupalFinder $drupalFinder
    ) {
        $this->generator = $generator;
        $this->configurationManager = $configurationManager;
        $this->drupalFinder = $drupalFinder;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:site:alias')
            ->setDescription(
                $this->trans('commands.generate.site.alias.description')
            )
            ->setHelp($this->trans('commands.generate.site.alias.help'))
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.name')
            )
            ->addOption(
                'environment',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.environment')
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.type')
            )
            ->addOption(
                'composer-root',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.composer-root')
            )
            ->addOption(
                'site-uri',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.site-uri')
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.host')
            )
            ->addOption(
                'user',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.user')
            )
            ->addOption(
                'port',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.port')
            )
            ->addOption(
                'extra-options',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.site.alias.options.extra-options')
            )
            ->addOption(
                'directory',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.site.alias.options.directory')
            )
            ->setAliases(['gsa']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(
        InputInterface $input,
        OutputInterface $output
    ) {
        $name = $input->getOption('name');
        if (!$name) {
            $sites = $this->configurationManager->getSites();
            if (!empty($sites)) {
                $sites = array_keys($this->configurationManager->getSites());
                $name = $this->getIo()->choiceNoList(
                    $this->trans('commands.generate.site.alias.questions.name'),
                    $sites,
                    current($sites),
                    TRUE
                );

                if (is_numeric($name)) {
                    $name = $sites[$name];
                }
            } else {
                $name = $this->getIo()->ask(
                    $this->trans('commands.generate.site.alias.questions.name')
                );
            }

            $input->setOption('name', $name);
        }

        $environment = $input->getOption('environment');
        if (!$environment) {
            $environment = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.environment'),
                null
            );

            $input->setOption('environment', $environment);
        }

        $type = $input->getOption('type');
        if (!$type) {
            $type = $this->getIo()->choiceNoList(
                $this->trans('commands.generate.site.alias.questions.type'),
                $this->types
            );

            $input->setOption('type', $type);
        }

        $composerRoot = $input->getOption('composer-root');
        if (!$composerRoot) {
            $root = $this->drupalFinder->getComposerRoot();
            if ($type === 'container') {
                $root = '/var/www/html';
            }
            if ($type === 'ssh') {
                $root = '/var/www/'.$name;
            }
            $composerRoot = $this->getIo()->ask(
                $this->trans('commands.generate.site.alias.questions.composer-root'),
                $root
            );

            $input->setOption('composer-root', $composerRoot);
        }

        $siteUri = $input->getOption('site-uri');
        if (!$siteUri) {
            $uri = explode('.', $environment);
            if (count($uri)>1) {
                $uri = $uri[1];
            } else {
                $uri = 'default';
            }
            $siteUri = $this->getIo()->askEmpty(
                $this->trans('commands.generate.site.alias.questions.site-uri'),
                $uri
            );

            $input->setOption('site-uri', $siteUri);
        }

        if ($type !== 'local') {
            $extraOptions = $input->getOption('extra-options');
            if (!$extraOptions) {
                $options = array_values($this->extraOptions[$type]);

                $extraOptions = $this->getIo()->choiceNoList(
                    $this->trans(
                        'commands.generate.site.alias.questions.extra-options'
                    ),
                    $options,
                    current($options)
                );

                $input->setOption('extra-options', $extraOptions);
            }

            $host = $input->getOption('host');
            if (!$host) {
                $host = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.site.alias.questions.host')
                );

                $input->setOption('host', $host);
            }

            $user = $input->getOption('user');
            if (!$user) {
                $user = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.site.alias.questions.user')
                );

                $input->setOption('user', $user);
            }

            $port = $input->getOption('port');
            if (!$port) {
                $port = $this->getIo()->askEmpty(
                    $this->trans('commands.generate.site.alias.questions.port')
                );

                $input->setOption('port', $port);
            }
        }

        $directory = $input->getOption('directory');
        if (!$directory) {
            $directory = $this->getIo()->choice(
                $this->trans('commands.generate.site.alias.questions.directory'),
                $this->configurationManager->getConfigurationDirectories()
            );

            $input->setOption('directory', $directory);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->generator->generate(
            [
                'name' => $input->getOption('name'),
                'environment' => $input->getOption('environment'),
                'type' => $input->getOption('type'),
                'extra_options' => $input->getOption('extra-options'),
                'root' => $input->getOption('composer-root'),
                'uri' => $input->getOption('site-uri'),
                'port' => $input->getOption('port'),
                'user' => $input->getOption('user'),
                'host' => $input->getOption('host'),
                'directory' => $input->getOption('directory')
            ]
        );
    }
}
