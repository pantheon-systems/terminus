<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;

/**
 * Class GetCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class GetCommand extends SingleBackupCommand implements RequestAwareInterface
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
     * @option string $element [code|files|database|db] Backup element to retrieve
     * @option string $to Local path to save to
     * @throws TerminusNotFoundException
     *
     * @usage <site>.<env> Displays the URL for the most recent backup of any type in <site>'s <env> environment.
     * @usage <site>.<env> --file=<file_name> Displays the URL for the backup with the file name <file_name> in <site>'s <env> environment.
     * @usage <site>.<env> --element=<element> Displays the URL for the most recent <element> backup in <site>'s <env> environment.
     * @usage <site>.<env> --to=<path> Saves the most recent backup of any type in <site>'s <env> environment to <path>.
     * @usage <site>.<env> --to=<path> Saves the most recent <element> backup in <site>'s <env> environment to <path>.
     */
    public function get($site_env, array $options = ['file' => null, 'element' => 'files', 'to' => null,])
    {
        $backup_url = $this->getBackup($site_env, $options)->getArchiveURL();
        if (!isset($options['to']) || is_null($save_path = $options['to'])) {
            return $backup_url;
        }
        $this->request()->download($backup_url, $save_path);
    }
}
