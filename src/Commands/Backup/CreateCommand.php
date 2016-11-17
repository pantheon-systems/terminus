<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CreateCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class CreateCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Creates a backup of the given environment
     *
     * @authorized
     *
     * @command backup:create
     *
     * @param string $site_env Site & environment to make a backup of, in the form `site-name.env`.
     * @option string $element [code|files|database|db] Create a backup of just the code, files, or database
     * @option integer $keep-for Set to retain the backup for a specific number of days
     *
     * @usage terminus backup:create awesome-site.dev
     *    Creates a backup of awesome-site's dev environment
     * @usage terminus backup:create awesome-site.live --element=database
     *    Creates a backup of awesome-site's live environment's database
     * @usage terminus backup:create awesome-site.dev --keep-for=10
     *    Creates a backup of awesome-site's dev environment and retain it for ten days
     * @usage terminus backup:create awesome-site.live --element=database --keep-for=15
     *    Creates a backup of awesome-site's live environment's database and retain it for 15 days
     */
    public function createBackup($site_env, $options = ['element' => null, 'keep-for' => 365,])
    {
        list(, $env) = $this->getSiteEnv($site_env);
        if (isset($options['element']) && ($options['element'] == 'db')) {
            $options['element'] = 'database';
        }
        $env->getBackups()->create($options)->wait();
        $this->log()->notice('Created a backup of the {env} environment.', ['env' => $env->id,]);
    }
}
