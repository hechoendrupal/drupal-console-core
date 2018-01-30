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
     * @param $showBy
     * @param $removeBy
     */
    private function add($type, $message, $code, $showBy, $removeBy)
    {
        $this->messages[] = [
            'type' =>$type,
            'message' => $message,
            'code' => $code,
            'showBy' => $showBy,
            'removeBy' => $removeBy,
        ];
    }

    /**
     * @param $message
     * @param $code
     * @param $showBy
     * @param $removeBy
     */
    public function error($message, $code = 0, $showBy = 'all', $removeBy = null)
    {
        $this->add('error', $message, $code, $showBy, $removeBy);
    }

    /**
     * @param $message
     * @param $code
     * @param $showBy
     * @param $removeBy
     */
    public function warning($message, $code = 0, $showBy = 'all', $removeBy = null)
    {
        $this->add('warning', $message, $code, $showBy, $removeBy);
    }

    /**
     * @param $message
     * @param $code
     * @param $showBy
     * @param $removeBy
     */
    public function info($message, $code = 0, $showBy = 'all', $removeBy = null)
    {
        $this->add('info', $message, $code, $showBy, $removeBy);
    }

    /**
     * @param $message
     * @param $code
     * @param $showBy
     * @param $removeBy
     */
    public function listing(array $message, $code = 0, $showBy = 'all', $removeBy = null)
    {
        $this->add('listing', $message, $code, $showBy, $removeBy);
    }

    /**
     * @param $message
     * @param $code
     * @param $showBy
     * @param $removeBy
     */
    public function comment($message, $code = 0, $showBy = 'all', $removeBy = null)
    {
        $this->add('comment', $message, $code, $showBy, $removeBy);
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
                if (is_null($message['removeBy'])) {
                    return true;
                }

                return !($message['removeBy'] == $removeBy);
            }
        );
    }
}
