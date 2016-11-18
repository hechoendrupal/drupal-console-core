<?php

/**
 * @file
 * Contains \Drupal\Console\Helper\RemoteHelper.
 */

namespace Drupal\Console\Utils;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Drupal\Console\Utils\TranslatorManager;

/**
 * Class RemoteHelper
 * @package \Drupal\Console\Helper\RemoteHelper
 */
class Remote
{
    /**
     * @var TranslatorManager
     */
    protected $translator;

    /**
     * Remote constructor.
     * @param $translator
     */
    public function __construct(
        TranslatorManager $translator
    ) {
        $this->translator = $translator;
    }

    /**
   * @param string $commandName
   * @param string $target
   * @param array  $targetConfig
   * @param array  $inputCommand
   * @param array  $userHomeDir
   * @return string
   */
    public function executeCommand(
        $commandName,
        $target,
        $targetConfig,
        $inputCommand,
        $userHomeDir
    ) {
        $remoteCommand = str_replace(
            [sprintf('\'%s\'', $commandName), sprintf('target=\'%s\'', $target), '--remote=1'],
            [$commandName, sprintf('root=%s', $targetConfig['root']), ''],
            $inputCommand
        );

        $remoteCommand = sprintf(
            '%s %s',
            $targetConfig['console'],
            $remoteCommand
        );

        $key = null;
        if (array_key_exists('password', $targetConfig)) {
            $key = $targetConfig['password'];
        }

        if (!$key) {
            $key = new RSA();
            if (array_key_exists('passphrase', $targetConfig['keys']) && !empty($targetConfig['keys']['passphrase'])) {
                $passphrase = $targetConfig['keys']['passphrase'];
                $passphrase = realpath(preg_replace('/~/', $userHomeDir, $passphrase, 1));
                $key->setPassword(trim(file_get_contents($passphrase)));
            }
            $private = $targetConfig['keys']['private'];
            $private = realpath(preg_replace('/~/', $userHomeDir, $private, 1));

            if (!$key->loadKey(trim(file_get_contents($private)))) {
                return $this->translator->trans('commands.site.debug.messages.private-key');
            }
        }

        $ssh = new SSH2($targetConfig['host'], $targetConfig['port']);
        if (!$ssh->login($targetConfig['user'], $key)) {
            return sprintf(
                '%s - %s',
                $ssh->getExitStatus(),
                $ssh->getErrors()
            );
        } else {
            return $ssh->exec($remoteCommand);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'remote';
    }
}
