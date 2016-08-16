<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\Generator.
 */

namespace Drupal\Console\Generator;

abstract class Generator
{
    protected $renderer;

    protected $fileQueue;

    /**
     * @param string $template
     * @param string $target
     * @param array  $parameters
     * @param null   $flag
     *
     * @return bool
     */
    protected function renderFile(
        $template,
        $target,
        $parameters,
        $flag = null
    )
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        $content = $this->renderer->render($template, $parameters);

         if (file_put_contents($target, $content, $flag)) {
            $this->fileQueue->addFile($target);

            return true;
        }

        return false;
    }
}
