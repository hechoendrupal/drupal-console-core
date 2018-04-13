<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Exclude\DrupliconCommand.
 */

namespace Drupal\Console\Core\Command\Exclude;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\TwigRenderer;
use Drupal\Console\Core\Utils\ConfigurationManager;

/**
 * Class DrupliconCommand
 *
 * @package Drupal\Console\Core\Command\Exclude
 */
class DrupliconCommand extends Command
{
    /**
     * @var string
     */
    protected $appRoot;

    /**
     * @var TwigRenderer
     */
    protected $renderer;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * DrupliconCommand constructor.
     *
     * @param string               $appRoot
     * @param TwigRenderer         $renderer
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        $appRoot,
        TwigRenderer $renderer,
        ConfigurationManager $configurationManager
    ) {
        $this->appRoot = $appRoot;
        $this->renderer = $renderer;
        $this->configurationManager = $configurationManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('druplicon')
            ->setDescription($this->trans('application.commands.druplicon.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = sprintf(
            '%s/templates/core/druplicon/',
            $this->configurationManager->getVendorCoreRoot()
        );

        $finder = new Finder();
        $finder->files()
            ->name('*.twig')
            ->in($directory);

        $templates = [];
        foreach ($finder as $template) {
            $templates[] = $template->getRelativePathname();
        }

        $druplicon = $this->renderer->render(
            sprintf(
                'core/druplicon/%s',
                $templates[array_rand($templates)]
            )
        );

        $this->getIo()->writeln($druplicon);
        return 0;
    }
}
