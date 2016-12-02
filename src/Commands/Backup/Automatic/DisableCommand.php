<?php

namespace Pantheon\Terminus\Commands\Backup\Automatic;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CancelCommand
 * @package Pantheon\Terminus\Commands\Backup\Automatic
 */
class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Disable a regular backup schedule
     *
     * @authorize
     *
     * @command backup:automatic:disable
     *
     * @param string $site_env Site & environment to disable the schedule of, in the format `site-name.env`.
     *
     * @usage terminus backup:automatic:disable <site>.<env>
     *    Disables the regular backup schedule for the <env> environment of <site>.
     */
    public function disableSchedule($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $env->getBackups()->cancelBackupSchedule();
        $this->log()->notice('Backup schedule successfully canceled.');
    }
}
