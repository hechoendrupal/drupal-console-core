<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\CommandTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Utils\TranslatorManager;

/**
 * Class CommandTrait
 * @package Drupal\Console\Command
 */
trait CommandTrait
{
    /**
     * @var  TranslatorManager
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
}
