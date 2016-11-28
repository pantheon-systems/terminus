<?php

namespace Pantheon\Terminus\Commands\Backup\Schedule;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\Backup\Schedule
 */
class SetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Set up a week-long TTL backups to be made daily and a month-long TTL to be made weekly
     *
     * @authorize
     *
     * @command backup:schedule:set
     *
     * @param string $site_env Site & environment to set the schedule of, in the format `site-name.env`.
     * @option string $day Day of the week to make the month-long backup in any format recognized by PHP strtotime
     *
     * @usage terminus backup:schedule:set <site>.<env>
     *     Sets backups to occur at a random hour, with month-long TTL backup made on a random day
     * @usage terminus backup:schedule:set <site>.<env> --day=<day>
     *     Sets backups to occur at a random hour, with month-long TTL backup made on <day>
     */
    public function setSchedule($site_env, $options = ['day' => null,])
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $env->getBackups()->setBackupSchedule($options);
        $this->log()->notice('Backup schedule successfully set.');
    }
}
