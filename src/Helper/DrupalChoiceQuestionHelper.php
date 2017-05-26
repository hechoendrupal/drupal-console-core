<?php
/**
 * @file
 * Contains \Drupal\Console\Core\Helper\DrupalChoiceQuestionHelper.
 */

namespace Drupal\Console\Core\Helper;

use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class DrupalChoiceQuestionHelper
 *
 * @package Drupal\Console\Core\Helper
 */
class DrupalChoiceQuestionHelper extends SymfonyQuestionHelper
{
    /**
     * {@inheritdoc}
     */
    protected function writePrompt(OutputInterface $output, Question $question)
    {
        $text = $question->getQuestion();
        $default = $question->getDefault();
        $choices = $question->getChoices();

        $text = sprintf(' <info>%s</info> [<comment>%s</comment>]:', $text, $choices[$default]);

        $output->writeln($text);

        $output->write(' > ');
    }
}
