<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class RestoreCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class RestoreCommand extends SingleBackupCommand
{
    use WorkflowProcessingTrait;

    /**
     * Restores a specific backup or the latest backup.
     *
     * @authorize
     *
     * @command backup:restore
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $file [filename.tgz] Name of backup file
     * @option string $element [all|code|files|database|db] Backup element
     * @throws TerminusException
     *
     * @usage <site>.<env> Restores the most recent backup of any type to <site>'s <env> environment.
     * @usage <site>.<env> --file=<backup> Restores backup with the <backup> file name to <site>'s <env> environment.
     * @usage <site>.<env> --element=<element> Restores the most recent <element> backup to <site>'s <env> environment.
     */
    public function restoreBackup($site_env, array $options = ['file' => null, 'element' => 'all',])
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $backup = $this->getBackup($site_env, $options);

        $tr = ['site' => $site->getName(), 'env' => $env->getName()];
        if (!$this->confirm('Are you sure you want to restore to {env} on {site}?', $tr)) {
            return;
        }

        $workflow = $backup->restore();
        try {
            $this->processWorkflow($workflow);
            $this->log()->notice('Restored the backup to {env}.', ['env' => $env->id,]);
        } catch (\Exception $e) {
            $message = $workflow->getMessage();
            if (trim($message) == 'Successfully queued restore_site') {
                $message = 'There was an error while restoring your backup.';
            }
            throw new TerminusException($message);
        }
    }
}
