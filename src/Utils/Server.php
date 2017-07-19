<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Utils\Server.
 */

namespace Drupal\Console\Core\Utils;

/**
 * Class Server
 *
 * @package Drupal\Console\Core\Utils
 */
class Server
{
    private $user;
    private $host;
    private $port;
    private $root;

    //just for test
    public function __construct(array $options) {
      $this->user = $options['user'];
      $this->host = $options['host'];
      $this->root = $options['root'];
    }

    public function __toString(): string
    {
        return sprintf('%s%s', $this->getUser() ? $this->getUser() . '@' : '', $this->getHost());
    }

    public function getSshConnectionString(): string
    {
        return sprintf('ssh -A -tt %s%s%s',
            $this->user ?? '',
            $this->user ? '@' . $this->host : $this->host,
            $this->port ? ' -p ' . $this->port : ''
        );
    }

    public function getRoot() : string
    {
        return $this->root;
    }
}
