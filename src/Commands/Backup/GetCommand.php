<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Class GetCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class GetCommand extends BackupCommand implements RequestAwareInterface
{
    use RequestAwareTrait;

    /**
     * Displays the download URL for a specific backup or latest backup.
     *
     * @authorize
     *
     * @command backup:get
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $file [filename.tgz] Name of backup file
     * @option string $element [all|code|files|database|db] Backup element to retrieve
     * @option string $to Local path to save to
     * @throws TerminusNotFoundException
     *
     * @usage <site>.<env> Displays the URL for the most recent backup of any type in <site>'s <env> environment.
     * @usage <site>.<env> --file=<file_name> Displays the URL for the backup with the file name <file_name> in <site>'s <env> environment.
     * @usage <site>.<env> --element=<element> Displays the URL for the most recent <element> backup in <site>'s <env> environment.
     * @usage <site>.<env> --to=<path> Saves the most recent backup of any type in <site>'s <env> environment to <path>.
     * @usage <site>.<env> --to=<path> Saves the most recent <element> backup in <site>'s <env> environment to <path>.
     */
    public function getBackup($site_env, array $options = ['file' => null, 'element' => 'all', 'to' => null,])
    {
        list($site, $env) = $this->getSiteEnv($site_env);

        if (isset($options['file']) && !is_null($file_name = $options['file'])) {
            $backup = $env->getBackups()->getBackupByFileName($file_name);
        } else {
            $element = isset($options['element']) ? $this->getElement($options['element']) : null;
            $backups = $env->getBackups()->getFinishedBackups($element);
            if (empty($backups)) {
                throw new TerminusNotFoundException(
                    'No backups available. Create one with `terminus backup:create {site}.{env}`',
                    ['site' => $site->get('name'), 'env' => $env->id,]
                );
            }
            $backup = array_shift($backups);
        }

        $backup_url = $backup->getUrl();
        if (!isset($options['to']) || is_null($save_path = $options['to'])) {
            return $backup_url;
        }
        $this->request()->download($backup_url, $save_path);
    }
}
