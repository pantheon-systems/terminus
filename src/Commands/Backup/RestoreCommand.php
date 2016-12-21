<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Class RestoreCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class RestoreCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Restores a specific backup or the latest backup.
     *
     * @authorize
     *
     * @command backup:restore
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $file [filename.tgz] Name of backup file
     * @option string $element [code|files|database|db] Backup element
     * @throws TerminusException
     *
     * @usage terminus backup:restore <site>.<env>
     *     Restores the most recent backup of any type to <site>'s <env> environment.
     * @usage terminus backup:restore <site>.<env> --file=<backup>
     *     Restores backup with the <backup> file name to <site>'s <env> environment.
     * @usage terminus backup:restore <site>.<env> --element=<element>
     *     Restores the most recent <element> backup to <site>'s <env> environment.
     */
    public function restoreBackup($site_env, array $options = ['file' => null, 'element' => null,])
    {
        list($site, $env) = $this->getSiteEnv($site_env);

        if (isset($options['file']) && !is_null($file_name = $options['file'])) {
            $backup = $env->getBackups()->getBackupByFileName($file_name);
        } else {
            $element = ($options['element'] == 'db') ? 'database' : $options['element'];
            $backups = $env->getBackups()->getFinishedBackups($element);
            if (empty($backups)) {
                throw new TerminusNotFoundException(
                    'No backups available. Create one with `terminus backup:create {site}.{env}`',
                    ['site' => $site->get('name'), 'env' => $env->id,]
                );
            }
            $backup = array_shift($backups);
        }

        $tr = ['site' => $site->getName(), 'env' => $env->getName()];
        if (!$this->confirm('Are you sure you want to restore to {env} on {site}?', $tr)) {
            return;
        }

        $workflow = $backup->restore();
        $workflow->wait();

        if ($workflow->isSuccessful()) {
            $this->log()->notice('Restored the backup to {env}.', ['env' => $env->id,]);
        } else {
            $message = $workflow->getMessage();
            if (trim($message) == 'Successfully queued restore_site') {
                $message = 'There was an error while restoring your backup.';
            }
            throw new TerminusException($message);
        }
    }
}
