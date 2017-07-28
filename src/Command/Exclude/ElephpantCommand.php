<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Command\Exclude\ElephpantCommand.
 */

namespace Drupal\Console\Core\Command\Exclude;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\TwigRenderer;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ElephpantCommand
 *
 * @package Drupal\Console\Core\Command\Exclude
 */
class ElephpantCommand extends Command
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
     * DrupliconCommand constructor.
     *
     * @param string       $appRoot
     * @param TwigRenderer $renderer
     */
    public function __construct(
        $appRoot,
        TwigRenderer $renderer
    ) {
        $this->appRoot = $appRoot;
        $this->renderer = $renderer;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('elephpant')
            ->setDescription($this->trans('application.commands.elephpant.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $directory = sprintf(
            '%stemplates/core/elephpant/',
            $this->appRoot . DRUPAL_CONSOLE_CORE
        );

        $finder = new Finder();
        $finder->files()
            ->name('*.twig')
            ->in($directory);

        $templates = [];

        foreach ($finder as $template) {
            $templates[] = $template->getRelativePathname();
        }

        $elephpant = $this->renderer->render(
            sprintf(
                'core/elephpant/%s',
                $templates[array_rand($templates)]
            )
        );

        $io->writeln($elephpant);
        return 0;
    }
}
