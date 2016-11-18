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
     * Create a backup of the specified environment
     *
     * @authorize
     *
     * @command backup:create
     *
     * @param string $site_env Site & environment to make a backup of, in the form `site-name.env`.
     * @option string $element [code|files|database|db] Create a backup of just the code, files, or database
     * @option integer $keep-for Set to retain the backup for a specific number of days
     *
     * @usage terminus backup:create <site>.<env>
     *    Creates a backup of the <env> environment of <site>
     * @usage terminus backup:create <site>.<env> --element=<element>
     *    Creates a backup of the <env> environment of <site>'s <element>
     * @usage terminus backup:create <site>.<env> --keep-for=<days>
     *    Creates a backup of the <env> environment of <site> and retains it for <days> days
     * @usage terminus backup:create <site>.<env> --element=<element> --keep-for=<days>
     *    Creates a backup of awesome-site's live environment's <element> and retain it for <days> days
     */
    public function create($site_env, $options = ['element' => null, 'keep-for' => 365,])
    {
        list(, $env) = $this->getSiteEnv($site_env);
        if (isset($options['element']) && ($options['element'] == 'db')) {
            $options['element'] = 'database';
        }
        $env->getBackups()->create($options)->wait();
        $this->log()->notice('Created a backup of the {env} environment.', ['env' => $env->id,]);
    }
}
