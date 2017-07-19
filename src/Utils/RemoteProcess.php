<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Utils\Server.
 */

namespace Drupal\Console\Core\Utils;

use Drupal\Console\Core\Utils\Server;
use Symfony\Component\Process\Process;

/**
 * Class Server
 *
 * @package Drupal\Console\Core\Utils
 */
class RemoteProcess {

    private $Server;

    public function __construct(Server $Server) {
        $this->Server = $Server;
    }

    public function run($command) {
        // cd path
        if ($this->Server->getRoot()) {
            $command = sprintf('cd %s && drupal %s && exit'. "\n", $this->Server->getRoot(), $command);
        }

        $process = new Process($this->Server->getSshConnectionString(), null, null, $command);
        $process->setTimeout(null);
        $process->start();
        $process->wait(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo($buffer);
            } else {
                echo($buffer);
            }
        });
        return $process;
    }
}
