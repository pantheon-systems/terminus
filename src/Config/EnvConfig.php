<?php

namespace Pantheon\Terminus\Config;

/**
 * Class EnvConfig
 * @package Pantheon\Terminus\Config
 */
class EnvConfig extends TerminusConfig
{
    protected $source_name = 'Environment Variable';

    /**
     * EnvConfig constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Add all of the environment vars that match our constant.
        foreach ([$_SERVER, $_ENV] as $super) {
            foreach ($super as $key => $val) {
                if ($this->keyIsConstant($key)) {
                    $this->set($key, $val);
                }
            }
        }
    }
}
