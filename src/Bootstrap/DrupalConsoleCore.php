<?php

namespace Drupal\Console\Bootstrap;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DrupalConsoleCore
{
    /**
     * @var string
     */
    protected $root;

    /**
     * DrupalConsole constructor.
     * @param $root
     */
    public function __construct($root)
    {
        $this->root = $root;
    }

    public function boot()
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($this->root));
        $loader->load($this->root.DRUPAL_CONSOLE_CORE.'/services.yml');
        if (file_exists($this->root.'/services.yml')) {
            $loader->load('services.yml');
        }

        $container->get('console.configuration_manager')
            ->loadConfiguration($this->root)
            ->getConfiguration();

        $container->get('console.translator_manager')
            ->loadCoreLanguage('en', $this->root);

        $container->set(
            'app.root',
            $this->root
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
