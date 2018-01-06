<?php
/**
 * @file
 * Contains \Drupal\Console\Core\Utils\ArgvInputReader.
 */

namespace Drupal\Console\Core\Utils;

use Symfony\Component\Console\Input\ArgvInput;

/**
 * Class ArgvInputReader.
 */
class ArgvInputReader
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var
     */
    protected $input;

    /**
     * @var array
     */
    protected $originalArgvValues;

    /**
     * ArgvInputReader constructor.
     */
    public function __construct()
    {
        $this->originalArgvValues = $_SERVER['argv'];
        $this->options = [];
        $this->setOptionsFromPlaceHolders();
        $this->readArgvInputValues();
    }

    /**
     * @param array $targetConfig
     */
    public function setOptionsFromTargetConfiguration($targetConfig)
    {
        $options = [];
        if (array_key_exists('root', $targetConfig)) {
            $options['root'] = $targetConfig['root'];
        }
        if (array_key_exists('uri', $targetConfig)) {
            $options['uri'] = $targetConfig['uri'];
        }

        if (array_key_exists('remote', $targetConfig)) {
            $this->set('remote', true);
        }

        $this->setArgvOptions($options);
    }

    /**
     * @param array $options
     */
    public function setOptionsFromConfiguration($options)
    {
        $this->setArgvOptions($options);
    }

    /**
     * @param $options
     */
    private function setArgvOptions($options)
    {
        $argvInput = new ArgvInput();
        foreach ($options as $key => $option) {
            if (!$option) {
                continue;
            }

            if (!$argvInput->hasParameterOption($key)) {
                if ($option == 1) {
                    $_SERVER['argv'][] = sprintf('--%s', $key);
                } else {
                    $_SERVER['argv'][] = sprintf('--%s=%s', $key, $option);
                }
                continue;
            }
            if ($key === 'root') {
                $option = sprintf(
                    '%s%s',
                    $argvInput->getParameterOption(['--root'], null),
                    $option
                );
            }
            foreach ($_SERVER['argv'] as $argvKey => $argv) {
                if (strpos($argv, '--'.$key) === 0) {
                    if ($option == 1) {
                        $_SERVER['argv'][$argvKey] = sprintf('--%s', $key);
                    } else {
                        $_SERVER['argv'][$argvKey] = sprintf(
                            '--%s=%s',
                            $key,
                            $option
                        );
                    }
                    continue;
                }
            }
        }
        $this->readArgvInputValues();
    }

    /**
     * setOptionsFromPlaceHolders.
     */
    private function setOptionsFromPlaceHolders()
    {
        if (count($_SERVER['argv']) > 2
            && stripos($_SERVER['argv'][1], '@') === 0
            && stripos($_SERVER['argv'][2], '@') === 0
        ) {
            $_SERVER['argv'][1] = sprintf(
                '--source=%s',
                substr($_SERVER['argv'][1], 1)
            );

            $_SERVER['argv'][2] = sprintf(
                '--target=%s',
                substr($_SERVER['argv'][2], 1)
            );

            return;
        }

        if (count($_SERVER['argv']) > 1 && stripos($_SERVER['argv'][1], '@') === 0) {
            $_SERVER['argv'][1] = sprintf(
                '--target=%s',
                substr($_SERVER['argv'][1], 1)
            );
        }
    }

    /**
     * ReadArgvInputValues.
     */
    private function readArgvInputValues()
    {
        $input = new ArgvInput();

        $source = $input->getParameterOption(['--source', '-s'], null);
        $target = $input->getParameterOption(['--target', '-t'], null);
        $root = $input->getParameterOption(['--root'], null);
        $debug = $input->hasParameterOption(['--debug']);
        $uri = $input->getParameterOption(['--uri', '-l']) ?: 'default';
        if ($uri && !preg_match('/^(http|https):\/\//', $uri)) {
            $uri = sprintf('http://%s', $uri);
        }

        $this->set('command', $input->getFirstArgument());
        $this->set('root', $root);
        $this->set('uri', $uri);
        $this->set('debug', $debug);
        $this->set('source', $source);
        $this->set('target', $target);
    }

    /**
     * @param $option
     * @param $value
     */
    public function set($option, $value)
    {
        if ($value) {
            $this->options[$option] = $value;

            return;
        }

        if (!array_key_exists($option, $this->options)) {
            unset($this->options[$option]);
        }
    }

    /**
     * @param $option
     * @param null   $value
     *
     * @return string
     */
    public function get($option, $value = null)
    {
        if (!array_key_exists($option, $this->options)) {
            return $value;
        }

        return $this->options[$option];
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->options;
    }

    /**
     * setOptionsAsArgv
     */
    public function setOptionsAsArgv()
    {
        foreach ($this->options as $optionName => $optionValue) {
            if ($optionName == 'command') {
                continue;
            }
            $optionFound = false;
            foreach ($_SERVER['argv'] as $key => $argv) {
                if (strpos($argv, '--'.$optionName) === 0) {
                    $_SERVER['argv'][$key] = '--'.$optionName.'='.$optionValue;
                    $optionFound = true;
                    break;
                }
            }
            if (!$optionFound) {
                $_SERVER['argv'][] = '--'.$optionName.'='.$optionValue;
            }
        }
    }

    /**
     * @return array
     */
    public function restoreOriginalArgvValues()
    {
        return $_SERVER['argv'] = $this->originalArgvValues;
    }
}
