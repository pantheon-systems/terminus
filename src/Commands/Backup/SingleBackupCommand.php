<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\BackupSet;

abstract class SingleBackupCommand extends BackupCommand
{
    /**
     * @param $site_env
     * @param array $options
     *
     * @return BackupSet
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function getBackup($site_env, array $options = ['file' => null, 'element' => 'all',]): BackupSet
    {
        $env = $this->getEnv($site_env);

        if (isset($options['file']) && !is_null($file_name = $options['file'])) {
            $backup = $env->getBackups()->getBackupByFileName($file_name);
        } else {
            $element = isset($options['element']) ? $this->getElement($options['element']) : null;
            $backups = $env->getBackups()->getFinishedBackups($element);
            if (empty($backups)) {
                throw new TerminusNotFoundException(
                    'No backups available. Create one with `terminus backup:create {site}.{env}`',
                    [
                        'site' => $this->getSite($site_env)->getName(),
                        'env' => $env->getName(),
                    ]
                );
            }
            $backup = array_shift($backups);
        }
        return $backup;
    }
}
