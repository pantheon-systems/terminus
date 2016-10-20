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
     * Sets up a week-long TTL backups to be made daily and a month-long TTL to be made weekly
     *
     * @authorized
     *
     * @command backup:schedule:set
     *
     * @param string $site_env Site & environment to set the schedule of, in the format `site-name.env`.
     * @option string  $day  Day of the week to make the month-long backup in any format recognized by PHP strtotime
     * @option integer $hour Hour of the day to make the backups at (0-23)
     *
     * @usage terminus backup:schedule:set awesome-site.dev
     *     Sets backups to occur at a random hour, with month-long TTL backup made on a random day
     * @usage terminus backup:schedule:set awesome-site.dev --day=<mon|Monday|etc>
     *     Sets backups to occur at a random hour, with month-long TTL backup made on Mondays
     * @usage terminus backup:schedule:set awesome-site.dev --hour=hour_number
     *     Sets backups to occur during the 14th hour UTCs, with month-long TTL backup made on a random day
     * @usage terminus backup:schedule:set awesome-site.dev --day=<TUE|Tuesday|etc> --hour=hour_number
     *     Sets backups to occur during the 14th hour UTC, with month-long TTL backup made on Tuesdays
     */
    public function setSchedule($site_env, $options = ['day' => null, 'hour' => null,])
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $env->backups->setBackupSchedule($options);
        $this->log()->notice('Backup schedule successfully set.');
    }
}
