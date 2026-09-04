<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Config;

use Robo\Common\ConfigAwareTrait as RoboConfigTrait;

trait ConfigAwareTrait
{
    use RoboConfigTrait {
        RoboConfigTrait::getConfig as __getConfig;
    }

    /**
     * Replaces same method in ConfigAwareTrait in order to provide a
     * TerminusConfig as return type. Helps with IDE completion.
     * As seen in Drush\Config\ConfigAwareTrait
     * https://raw.githubusercontent.com/drush-ops/drush/master/src/Config/ConfigAwareTrait.php
     *
     * @see https://stackoverflow.com/a/37687295.
     *
     * @return TerminusConfig
     */
    public function getConfig(): TerminusConfig
    {
        return $this->__getConfig();
    }
}
