<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Commands\WorkflowProcessingTrait;

/**
 * Class CreateCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class CreateCommand extends BackupCommand
{
    use WorkflowProcessingTrait;

    /**
     * Creates a backup of a specific site and environment.
     *
     * @authorize
     *
     * @command backup:create
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $element [all|code|files|database|db] Element to be backed up
     * @option integer $keep-for Retention period, in days, to retain backup
     *
     * @usage <site>.<env> Creates a backup of <site>'s <env> environment.
     * @usage <site>.<env> --element=<element> Creates a backup of <site>'s <env> environment's <element>.
     * @usage <site>.<env> --keep-for=<days> Creates a backup of <site>'s <env> environment and retains it for <days> days.
     * @usage <site>.<env> --keep-for=<days> Creates a backup of <site>'s <env> environment's <element> and retains it for <days> days.
     */
    public function create($site_env, $options = ['element' => 'all', 'keep-for' => 365,])
    {
        list(, $env) = $this->getUnfrozenSiteEnv($site_env);
        $options['element'] = isset($options['element']) ? $this->getElement($options['element']) : null;
        $this->processWorkflow($env->getBackups()->create($options));
        $this->log()->notice('Created a backup of the {env} environment.', ['env' => $env->id,]);
    }
}
