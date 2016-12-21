<?php

namespace Pantheon\Terminus\Commands\Self;

use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class ConsoleCommand
 * @package Pantheon\Terminus\Commands\Self
 */
class ConsoleCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    /**
     * Initiate an interactive PHP console
     *
     * @command self:console
     *
     * @option string $site_env Site & environment to access as `$site` and (optional) `$env`
     *
     * @usage
     *   Initiates an an interactive PHP console
     * @usage <site>
     *   Initiates an interactive PHP console with access to an object representing <site>
     * @usage <site>.<env>
     *   Initiates an interactive PHP console with access to an object representing <site> and its <env> environment
     *
     */
    public function console($site_env = null)
    {
        list($site, $env) = $this->getOptionalSiteEnv($site_env, null);
        eval(\Psy\sh());
    }
}
