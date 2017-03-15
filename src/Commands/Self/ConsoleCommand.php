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
     * Opens an interactive PHP console within Terminus.
     * Note: This functionality is useful for debugging Terminus or prototyping Terminus plugins.
     *
     * @command self:console
     *
     * @option string $site_env Site & environment to access as `$site` and (optional) `$env`
     *
     * @usage Opens an interactive PHP console within Terminus.
     * @usage <site> Opens an interactive PHP console within Terminus and loads <site> as $site.
     * @usage <site>.<env> Opens an interactive PHP console within Terminus and loads <site> and its <env> environment as $site and $env.
     */
    public function console($site_env = null)
    {
        list($site, $env) = $this->getOptionalSiteEnv($site_env, null);
        eval(\Psy\sh());
    }
}
