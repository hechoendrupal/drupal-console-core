<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Bootstrap.
 */

namespace Drupal\Console\Core\Bootstrap;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class DrupalConsoleCore
 *
 * @package Drupal\Console\Core\Bootstrap
 */
class DrupalConsoleCore
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * DrupalConsole constructor.
     *
     * @param $root
     * @param $appRoot
     */
    public function __construct($root, $appRoot = null)
    {
        $this->root = $root;
        $this->appRoot = $appRoot;
    }

    /**
     * @return ContainerBuilder
     */
    public function boot()
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($this->root));

        $servicesFiles = [
            $this->root.DRUPAL_CONSOLE_CORE.'/services.yml',
            $this->root.'/services.yml',
            $this->root.DRUPAL_CONSOLE.'/uninstall.services.yml',
            $this->root.DRUPAL_CONSOLE.'/extend.console.uninstall.services.yml'
        ];

        foreach ($servicesFiles as $servicesFile) {
            if (file_exists($servicesFile)) {
                $loader->load($servicesFile);
            }
        }

        $container->get('console.configuration_manager')
            ->loadConfiguration($this->root)
            ->getConfiguration();

        $container->get('console.translator_manager')
            ->loadCoreLanguage('en', $this->root);

        $appRoot = $this->appRoot?$this->appRoot:$this->root;
        $container->set(
            'app.root',
            $appRoot
        );
        $consoleRoot = $appRoot;
        if (stripos($this->root, '/bin/') <= 0) {
            $consoleRoot = $this->root;
        }
        $container->set(
            'console.root',
            $consoleRoot
        );

        $container->get('console.renderer')
            ->setSkeletonDirs(
                [
                    $this->root.'/templates/',
                    $this->root.DRUPAL_CONSOLE_CORE.'/templates/'
                ]
            );

        return $container;
    }
}
