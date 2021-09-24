<?php

namespace Pantheon\Terminus\Commands\Backup\Automatic;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DisableCommand.
 *
 * @package Pantheon\Terminus\Commands\Backup\Automatic
 */
class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Disables automatic backups.
     *
     * @authorize
     *
     * @command backup:automatic:disable
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Disables the regular backup schedule for <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function disableSchedule($site_env)
    {
        $this->getEnv($site_env)->getBackups()->cancelBackupSchedule();
        $this->log()->notice('Backup schedule successfully canceled.');
    }
}
