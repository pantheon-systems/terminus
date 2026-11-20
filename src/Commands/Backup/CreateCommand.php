<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
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
     * @interact
     *
     * @command backup:create
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param array $options
     * @option string $element [all|code|files|database|db] Element to be backed up
     * @option integer $keep-for Retention period, in days, to retain backup. It defaults to 30 days and can be at most 180 days.
     *
     * @usage <site>.<env> Creates a backup of <site>'s <env> environment.
     * @usage <site>.<env> --element=<element> Creates a backup of <site>'s <env> environment's <element>.
     * @usage <site>.<env> --keep-for=<days> Creates a backup of <site>'s <env> environment and retains it for <days> days.
     * @usage <site>.<env> --element=<element> --keep-for=<days> Creates a backup of <site>'s <env> environment's <element> and retains it for <days> days.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function create($site_env, $options = ['element' => 'all', 'keep-for' => null,])
    {
        $options['keep-for'] = $this->validateKeepFor($options['keep-for'] ?? null);
        $this->requireSiteIsNotFrozen($site_env);
        $env = $this->getEnv($site_env);

        $options['element'] = isset($options['element']) ? $this->getElement($options['element']) : null;
        $this->processWorkflow($env->getBackups()->create($options));
        $this->log()->notice(
            'Created a backup of the {env} environment.',
            ['env' => $env->getName()]
        );
    }

    /**
     * Returns the retention period in days, checking it against the allowed range before any API call is made.
     *
     * @param mixed $keep_for The --keep-for value, or null when it was not given
     * @return int
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function validateKeepFor($keep_for): int
    {
        if (is_null($keep_for)) {
            return Backup::DEFAULT_TTL;
        }
        if (filter_var($keep_for, FILTER_VALIDATE_INT) === false || (int)$keep_for < 1) {
            throw new TerminusException(
                '--keep-for must be a whole number of days between 1 and {max}, got "{value}".',
                ['max' => Backup::MAX_TTL, 'value' => $keep_for]
            );
        }
        if ((int)$keep_for > Backup::MAX_TTL) {
            throw new TerminusException(
                'Manual backups can be retained for at most {max} days, but --keep-for={value} was given. '
                . 'Use --keep-for={max} or less.',
                ['max' => Backup::MAX_TTL, 'value' => $keep_for]
            );
        }
        return (int)$keep_for;
    }
}
