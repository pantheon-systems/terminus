<?php

namespace Pantheon\Terminus\Commands\Backup\Automatic;

use Pantheon\Terminus\Collections\Backups;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\Backup\Automatic
 */
class EnableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Enables automatic daily backups that are retained for one week and weekly backups retained for one month.
     *
     * @authorize
     *
     * @command backup:automatic:enable
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $day Day of the week to make the month-long backup in any format recognized by PHP strtotime
     * @option integer $keep-for Retention period, in days, to retain the weekly backup. The default is 32 days.
     *
     * @usage <site>.<env> Enables automatic daily backups of <site>'s <env> environment that are retained for one week and weekly backups that are retained for one month.
     * @usage <site>.<env> --day=<day> Enables automatic daily backups of <site>'s <env> environment that are retained for one week and weekly backups on <day> that are retained for one month.
     * @usage <site>.<env> --keep-for=<days> Enables automatic daily backups of <site>'s <env> environment that are retained for one week and weekly backups on <day> that are retained for <days>.
     */
    public function enableSchedule($site_env, $options = ['day' => null, 'keep-for' => null,])
    {
        list(, $env) = $this->getSiteEnv($site_env);

        // Schedule Backup workflow expects 'weekly-ttl' as input option.
        if(isset($options['keep-for']) && !is_null($options['keep-for'])) {
            $options['weekly-ttl'] = $options['keep-for'];
        }

        $env->getBackups()->setBackupSchedule($options);
        $this->log()->notice('Backup schedule successfully set.');
    }
}
