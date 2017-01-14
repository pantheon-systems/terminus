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
     * Creates a backup of a specific site and environment.
     *
     * @authorize
     *
     * @command backup:create
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $element [code|files|database|db] Element to be backed up
     * @option integer $keep-for Retention period, in days, to retain backup
     *
     * @usage terminus backup:create <site>.<env>
     *     Creates a backup of <site>'s <env> environment.
     * @usage terminus backup:create <site>.<env> --element=<element>
     *     Creates a backup of <site>'s <env> environment's <element>.
     * @usage terminus backup:create <site>.<env> --keep-for=<days>
     *     Creates a backup of <site>'s <env> environment and retains it for <days> days.
     * @usage terminus backup:create <site>.<env> --element=<element> --keep-for=<days>
     *     Creates a backup of <site>'s <env> environment's <element> and retains it for <days> days.
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
