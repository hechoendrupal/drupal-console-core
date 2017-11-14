<?php

namespace Drupal\Console\Core\Utils;

/**
 * Class MessageParser
 * @package Drupal\Console\Core\Utils
 */
class MessageParser {

    /**
     * @param $container
     * @param $type
     * @param $message
     */
    public function addMessage($container, $type, $message) {

        $messages = [];
        if ($container->hasParameter('console.messages')) {
            $messages = $container->getParameter('console.messages');
        }
        $messages[] = [ $type => $message ];

        $container
            ->setParameter(
                'console.messages',
                $messages
            );
    }
}