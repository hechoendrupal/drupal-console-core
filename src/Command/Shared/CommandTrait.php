<?php

/**
 * @file
 * Contains Drupal\Console\Core\Command\Shared\CommandTrait.
 */

namespace Drupal\Console\Core\Command\Shared;

use Drupal\Console\Core\Utils\TranslatorManagerInterface;

/**
 * Class CommandTrait
 *
 * @package Drupal\Console\Core\Command
 */
trait CommandTrait
{
    /**
     * @var TranslatorManagerInterface
     */
    protected $translator;

    /**
     * @param $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param $key string
     *
     * @return string
     */
    public function trans($key)
    {
        if (!$this->translator) {
            return $key;
        }

        return $this->translator->trans($key);
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        $description = sprintf(
            'commands.%s.description',
            str_replace(':', '.', $this->getName())
        );

        if (parent::getDescription()==$description) {
            return $this->trans($description);
        }

        return parent::getDescription();
    }

    /**
   * @inheritdoc
   */
    public function getHelp()
    {
        $help = sprintf(
            'commands.%s.help',
            str_replace(':', '.', $this->getName())
        );

        if (parent::getHelp()==$help) {
            return $this->trans($help);
        }

        return parent::getHelp();
    }
}
