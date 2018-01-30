<?php
/**
 * Created by PhpStorm.
 * User: jmolivas
 * Date: 1/16/18
 * Time: 2:22 PM
 */

namespace Drupal\Console\Core\Command\Chain;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Utils\ChainDiscovery;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    /**
     * @var ChainDiscovery
     */
    protected $chainDiscovery;

    /**
     * BaseCommand constructor.
     *
     * @param ChainDiscovery $chainDiscovery
     */
    public function __construct(
        ChainDiscovery $chainDiscovery
    ) {
        $this->chainDiscovery = $chainDiscovery;
        parent::__construct();
        $this->ignoreValidationErrors();
    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize($input, $output);

        $options = [];
        foreach ($_SERVER['argv'] as $index => $element) {
            if ($index<2) {
                continue;
            }

            if (substr($element, 0, 2) !== "--") {
                continue;
            }

            $element = substr($element, 2);
            $exploded = explode("=", $element);

            if (!$exploded) {
                $exploded = explode(" ", $element);
            }

            if (count($exploded)>1) {
                $options[trim($exploded[0])] = trim($exploded[1]);
            }
        }

        $file = $input->getOption('file');
        $file = calculateRealPath($file);
        $content = $this->chainDiscovery->getFileContents($file);
        $variables = $this->chainDiscovery->extractInlinePlaceHolderNames($content);

        foreach ($variables as $variable) {
            if (!array_key_exists($variable, $options)) {
                $options[$variable] = null;
            }
        }

        foreach ($options as $optionName => $optionValue) {
            if ($input->hasOption($optionName)) {
                continue;
            }

            $this->addOption(
                $optionName,
                null,
                InputOption::VALUE_OPTIONAL,
                $optionName,
                $optionValue
            );
        }
    }

    protected function getFileOption()
    {
        $input = $this->getIo()->getInput();
        $file = $input->getOption('file');

        if (!$file) {
            $files = array_keys($this->chainDiscovery->getFiles());

            $file = $this->getIo()->choice(
                $this->trans('commands.chain.questions.chain-file'),
                $files
            );
        }

        $file = calculateRealPath($file);
        $input->setOption('file', $file);

        return $file;
    }

    protected function getOptionsAsArray()
    {
        $input = $this->getIo()->getInput();
        $options = [];
        foreach ($input->getOptions() as $option => $value) {
            $options[$option] =  $value;
        }

        return $options;
    }
}
