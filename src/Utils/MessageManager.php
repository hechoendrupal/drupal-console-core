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
     * @param $removableBy
     */
    private function add($type, $message, $code, $removableBy)
    {
        $this->messages[] = [
            'type' =>$type,
            'message' => $message,
            'code' => $code,
            'removableBy' => $removableBy
        ];
    }

    /**
     * @param $message
     * @param $code
     * @param $removableBy
     */
    public function error($message, $code = 0, $removableBy = null)
    {
        $this->add('error', $message, $code, $removableBy);
    }

    /**
     * @param $message
     * @param $code
     * @param $removableBy
     */
    public function warning($message, $code = 0, $removableBy = null)
    {
        $this->add('warning', $message, $code, $removableBy);
    }

    /**
     * @param $message
     * @param $code
     * @param $removableBy
     */
    public function info($message, $code = 0, $removableBy = null)
    {
        $this->add('info', $message, $code, $removableBy);
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    public function remove($removeBy = null)
    {
        $this->messages = array_filter(
            $this->messages,
            function ($message) use ($removeBy) {
                if (is_null($message['removableBy'])) {
                    return true;
                }

                return !($message['removableBy'] == $removeBy);
            }
        );
    }
}
