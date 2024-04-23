<?php

namespace Pantheon\Terminus\CI;

use DirectoryIterator;
use Pantheon\Terminus\CI\Traits\TerminusBinaryTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Config\DefaultsConfig;
use Pantheon\Terminus\Config\DotEnvConfig;
use Pantheon\Terminus\Config\EnvConfig;
use Pantheon\Terminus\Config\YamlConfig;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Process\Process;

/**
 * Class CIApplication
 * @package Pantheon\Terminus\CI
 * @mixin CICommandBase
 *
 */
class CIApplication extends Application implements
    ConfigAwareInterface
{
    use ConfigAwareTrait;
    use TerminusBinaryTrait;

    /**
     *
     */
    public function __construct()
    {
        // Use the standard Terminus config system so the values are always the same
        // Configs are loaded in the order they are added, so the last one wins
        $config = new DefaultsConfig();
        $config->extend(new YamlConfig($this->getProjectRoot() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'constants.yml'));
        $config->extend(new YamlConfig($config->get('user_home') . DIRECTORY_SEPARATOR . '.terminus' . DIRECTORY_SEPARATOR . 'config.yml'));
        $config->extend(new DotEnvConfig(getcwd()));
        $config->extend(new EnvConfig());
        $this->setConfig($config);

        // The version of the CI app will always be the version of Terminus
        parent::__construct('CI', $config->get('version'));
        $this->add(new RunTestsCommand());
        $dispatcher = new CIFixtureDispatcher();
        $this->setDispatcher($dispatcher);
    }




    /**
     * @return $this
     */
    final protected function ci(): CIApplication
    {
        return $this;
    }


    /**
     * @return bool
     */
    final protected function bootstrapSession(): bool
    {
        $this->ensureDirectories();
        // if there is no pre-existing session, grab the machine token
        // and use it to login to create a session
        if (!$this->hasSession()) {
            $proc = new Process(
                [
                    TERMINUS_BIN_FILE,
                    'auth:login',
                    '--machine-token=' . $this->getMachineToken(),
                ]
            );
            return $proc->run() === 0;
        }
        return true;
    }

    /**
     * @return string
     */
    final public function getMachineToken(): string
    {
        if (!empty(getenv('TERMINUS_TOKEN'))) {
            return getenv('TERMINUS_TOKEN');
        }
        $dir = new DirectoryIterator($this->getConfig()->get('tokens_dir'));
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                // Take the first token we find
                $userInfo = json_decode(file_get_contents($fileinfo->getPathname()));
                return $userInfo->token ?? '';
            }
        }
        // No token found
        return '';
    }

    /**
     * @return bool
     */
    final protected function hasSession(): bool
    {
        return file_exists($this->getConfig()->get('cache_dir') . DIRECTORY_SEPARATOR . 'session');
    }

    /**
     * @return void
     */
    final protected function ensureDirectories(): void
    {
        $testcache_dir = implode(DIRECTORY_SEPARATOR, [ $this->getConfig()->get('base_dir'), 'testcache']);
        if (!is_dir($testcache_dir)) {
            mkdir(
                $testcache_dir,
                0700,
                true
            );
        }
        $cache_dir = $this->getConfig()->get('cache_dir');
        if (!is_dir($cache_dir)) {
            mkdir(
                $cache_dir,
                0700,
                true
            );
        }
    }

    final public function getTestCacheDir(): string
    {
        return implode(DIRECTORY_SEPARATOR, [ $this->getConfig()->get('base_dir'), 'testcache']);
    }
}
