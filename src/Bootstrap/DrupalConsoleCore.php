<?php

/**
 * @file
 * Contains \Drupal\Console\Core\Bootstrap.
 */

namespace Drupal\Console\Core\Bootstrap;

use Drupal\Console\Core\Utils\DrupalFinder;
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
     * @var DrupalFinder
     */
    protected $drupalFinder;

    /**
     * DrupalConsole constructor.
     *
     * @param string       $root
     * @param string       $appRoot
     * @param DrupalFinder $drupalFinder
     */
    public function __construct(
        $root,
        $appRoot = null,
        DrupalFinder $drupalFinder
    ) {
        $this->root = $root;
        $this->appRoot = $appRoot;
        $this->drupalFinder = $drupalFinder;
    }

    /**
     * @return ContainerBuilder
     */
    public function boot()
    {
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($this->root));

        if (substr($this->root, -1) === DIRECTORY_SEPARATOR) {
            $this->root = substr($this->root, 0, -1);
        }

        $servicesFiles = [
            $this->root.DRUPAL_CONSOLE_CORE.'services.yml',
            $this->root.'/services.yml',
            $this->root.DRUPAL_CONSOLE.'uninstall.services.yml',
            $this->root.DRUPAL_CONSOLE.'extend.console.uninstall.services.yml'
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

        $container->set(
            'console.drupal_finder',
            $this->drupalFinder
        );

        $configurationManager = $container->get('console.configuration_manager');
        $directory = $configurationManager->getConsoleDirectory() . 'extend/';
        $autoloadFile = $directory . 'vendor/autoload.php';
        if (is_file($autoloadFile)) {
            include_once $autoloadFile;
            $extendServicesFile = $directory . 'extend.console.uninstall.services.yml';
            if (is_file($extendServicesFile)) {
                $loader->load($extendServicesFile);
            }
        }

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
