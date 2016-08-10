<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\CommandTrait.
 */

namespace Drupal\Console\Command\Shared;

//use Drupal\Console\Utils\TranslatorManager;

/**
 * Class CommandTrait
 * @package Drupal\Console\Command
 */
trait CommandTrait
{
//    /**
//     * @var  TranslatorManager
//     */
//    protected $translator;
//
//    /**
//     * @param $translator
//     */
//    public function setTranslator($translator)
//    {
//        $this->translator = $translator;
//    }

    /**
     * @param $key
     * @return null|object
     */
    public function get($key)
    {
        if (!$key) {
            return null;
        }

        if ($this->getApplication()->getContainer()->has($key)) {
            return $this->getApplication()->getContainer()->get($key);
        }
    }

    /**
     * @param $key string
     *
     * @return string
     */
    public function trans($key)
    {
        if (!$this->get('console.translator_manager')) {
            return $key;
        }

        return $this->get('console.translator_manager')->trans($key);
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

//    public function getApplication()
//    {
//        return parent::getApplication();
//    }

//    public function getOptions()
//    {
//        return parent::getOptions();
//    }
//
//    public function getArguments()
//    {
//        return parent::getArguments();
//    }
}
