<?php

/**
 * @file
 * Contains Drupal\Console\Core\Command\Shared\InputTrait.
 */

namespace Drupal\Console\Core\Command\Shared;

/**
 * Class InputTrait
 *
 * @package Drupal\Console\Core\Command\Shared
 */
trait InputTrait
{
    /**
     * @return array
     */
    private function inlineValueAsArray($inputValue)
    {
        $inputAsArray = [];
        foreach ($inputValue as $key => $value) {
            if (!is_array($value)) {
                try {
                    $inputAsArray[] = json_decode('[{'.$value.'}]', true)[0];
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $inputAsArray?$inputAsArray:$inputValue;
    }
}
