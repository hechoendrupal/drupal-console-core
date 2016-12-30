<?php
/**
 * @file
 * Contains \Drupal\Console\Core\Command\ListCommand.
 */
namespace Drupal\Console\Core\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Core\Helper\DescriptorHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class ListCommand
 * @package Drupal\Console\Core\Command
 */
class ListCommand extends Command
{
    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('list')
            ->setDefinition($this->createDefinition())
            ->setDescription($this->trans('commands.list.description'))
            ->setHelp($this->trans('commands.list.help'));
    }

    /**
     * {@inheritdoc}
     */
    public function getNativeDefinition()
    {
        return $this->createDefinition();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        if ($input->getOption('xml')) {
            $io->info(
                'The --xml option was deprecated in version 2.7 and will be removed in version 3.0. Use the --format option instead',
                E_USER_DEPRECATED
            );
            $input->setOption('format', 'xml');
        }
        $helper = new DescriptorHelper();
        $helper->describe(
            $io,
            $this->getApplication(),
            [
                'format' => $input->getOption('format'),
                'raw_text' => $input->getOption('raw'),
                'namespace' => $input->getArgument('namespace'),
                'translator' => $this->getApplication()->getTranslator()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    private function createDefinition()
    {
        return new InputDefinition(
            array(
                new InputArgument('namespace', InputArgument::OPTIONAL, $this->trans('commands.list.arguments.namespace')),
                new InputOption('xml', null, InputOption::VALUE_NONE, $this->trans('commands.list.options.xml')),
                new InputOption('raw', null, InputOption::VALUE_NONE, $this->trans('commands.list.options.raw')),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, $this->trans('commands.list.options.format'), 'txt'),
            )
        );
    }
}
