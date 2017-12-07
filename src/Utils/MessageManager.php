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
     * @param $isRemovable
     */
    private function add($type, $message, $code, $isRemovable)
    {
        $this->messages[] = [
            'type' =>$type,
            'message' => $message,
            'code' => $code,
            'isRemovable' => $isRemovable
        ];
    }

    /**
     * @param $message
     * @param $code
     * @param $isRemovable
     */
    public function error($message, $code = 0, $isRemovable = false)
    {
        $this->add('error', $message, $code, $isRemovable);
    }

    /**
     * @param $message
     * @param $code
     * @param $isRemovable
     */
    public function warning($message, $code = 0, $isRemovable = false)
    {
        $this->add('warning', $message, $code, $isRemovable);
    }

    /**
     * @param $message
     * @param $code
     * @param $isRemovable
     */
    public function info($message, $code = 0, $isRemovable = false)
    {
        $this->add('info', $message, $code, $isRemovable);
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    public function remove($type, $code = null) {
        $this->messages = array_filter(
            $this->messages,
            function ($element) use ($type, $code)  {

                if (!$element['isRemovable']) {

                    return true;
                }

                if ($type != 'all' && $element['type'] != $type) {

                    return true;
                }


                if (is_null($code)) {
                    return false;
                }

                return !($element['code'] == $code);
            }
        );
    }
}
