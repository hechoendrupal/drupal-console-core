<?php

namespace Drupal\Console\Core\Utils;

/**
 * Class MessageParser
 *
 * @package Drupal\Console\Core\Utils
 */
class MessageManager
{

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @param $type
     * @param $message
     * @param $code
     */
    private function add($type, $message, $code)
    {
        $this->messages[] = [
            'type' =>$type,
            'message' => $message,
            'code' => $code,
        ];
    }

    /**
     * @param $message
     * @param $code
     */
    public function error($message, $code = 0)
    {
        $this->add('error', $message, $code);
    }

    /**
     * @param $message
     * @param $code
     */
    public function warning($message, $code = 0)
    {
        $this->add('warning', $message, $code);
    }

    /**
     * @param $message
     * @param $code
     */
    public function info($message, $code = 0)
    {
        $this->add('info', $message, $code);
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
