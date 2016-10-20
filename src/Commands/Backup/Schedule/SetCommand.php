<?php

namespace Pantheon\Terminus\Commands\Backup\Schedule;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
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
     * Fetch the download URL for a specific backup or latest backup
     *
     * @authorized
     *
     * @command backup:schedule:set
     *
     * @param string $site_env Site & environment to set the schedule of, in the format `site-name.env`.
     * @option string  $day  Day of the week to make the month-long backup
     * @option integer $hour Hour of the day to make the backups at (1-24)
     * @return AssociativeList
     *
     * @field-labels
     *    daily_backup_hour: Daily Backup Hour
     *    weekly_backup_day: Weekly Backup Day
     * @default-string-field weekly_backup_day
     *
     * @usage terminus backup:schedule:get awesome-site.dev
     *     Responds with the day of the week backups are scheduled for
     * @usage terminus backup:schedule:get awesome-site.dev --format=table
     *     Responds with the day of the week and hour of the day backups are scheduled for
     */
    public function setSchedule($site_env, $options = ['day' => null, 'hour' => null,])
    {
        list(, $env) = $this->getSiteEnv($site_env, 'dev');
        $env->backups->setBackupSchedule($options);
        $this->log()->notice('Backup schedule successfully set.');
    }
}
