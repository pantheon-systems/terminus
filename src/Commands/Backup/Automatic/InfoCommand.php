<?php

namespace Pantheon\Terminus\Commands\Backup\Automatic;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class GetCommand
 * @package Pantheon\Terminus\Commands\Backup\Automatic
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Retrieve the regular backup of your site's environment
     *
     * @authorize
     *
     * @command backup:automatic:info
     *
     * @field-labels
     *    daily_backup_hour: Daily Backup Hour
     *    weekly_backup_day: Weekly Backup Day
     * @default-string-field weekly_backup_day
     * @return PropertyList
     *
     * @param string $site_env Site & environment to get the schedule of, in the format `site-name.env`.
     *
     * @usage terminus backup:automatic:info <site>.<env>
     *     Responds with the day of the week backups are scheduled for on the <env> environment of <site>
     * @usage terminus backup:automatic:info awesome-site.dev --format=table
     *     Responds with the day of the week and hour of the day backups are scheduled for on <site>.<env>
     */
    public function getSchedule($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $schedule = $env->getBackups()->getBackupSchedule();
        if (is_null($schedule['daily_backup_hour'])) {
            $this->log()->notice('Backups are not currently scheduled to be run.');
        }
        return new PropertyList($schedule);
    }
}
