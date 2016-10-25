<?php

namespace Pantheon\Terminus\Commands\Backup\Schedule;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class GetCommand
 * @package Pantheon\Terminus\Commands\Backup\Schedule
 */
class GetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Retrieve the regular backup of your site's environment
     *
     * @authorized
     *
     * @command backup:schedule:get
     *
     * @param string $site_env Site & environment to get the schedule of, in the format `site-name.env`.
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
    public function getSchedule($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $schedule = $env->backups->getBackupSchedule();
        if (is_null($schedule['daily_backup_hour'])) {
            $this->log()->notice('Backups are not currently scheduled to be run.');
        }
        return new AssociativeList($schedule);
    }
}
