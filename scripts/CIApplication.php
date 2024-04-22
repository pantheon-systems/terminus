<?php

namespace Pantheon\Terminus\CI;

use Pantheon\Terminus\CI\Traits\TerminusBinaryTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Config\DefaultsConfig;
use Pantheon\Terminus\Config\DotEnvConfig;
use Pantheon\Terminus\Config\EnvConfig;
use Pantheon\Terminus\Config\YamlConfig;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\Console\Application;

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
}
