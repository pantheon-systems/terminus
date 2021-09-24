<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Models\Backup;

/**
 * Class CreateCommand.
 *
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
     * @param array $options
     * @option string $element [all|code|files|database|db] Element to be backed up
     * @option integer $keep-for Retention period, in days, to retain backup. It defaults to 365 days.
     *
     * @usage <site>.<env> Creates a backup of <site>'s <env> environment.
     * @usage <site>.<env> --element=<element> Creates a backup of <site>'s <env> environment's <element>.
     * @usage <site>.<env> --keep-for=<days> Creates a backup of <site>'s <env> environment and retains it for <days> days.
     * @usage <site>.<env> --keep-for=<days> Creates a backup of <site>'s <env> environment's <element> and retains it for <days> days.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function create($site_env, $options = ['element' => 'all', 'keep-for' => null,])
    {
        $this->requireSiteIsNotFrozen($site_env);
        $env = $this->getEnv($site_env);

        $options['keep-for'] = (isset($options['keep-for']) && !is_null($options['keep-for']))
            ? $options['keep-for']
            : Backup::DEFAULT_TTL;
        $options['element'] = isset($options['element']) ? $this->getElement($options['element']) : null;
        $this->processWorkflow($env->getBackups()->create($options));
        $this->log()->notice(
            'Created a backup of the {env} environment.',
            ['env' => $env->getName()]
        );
    }
}
