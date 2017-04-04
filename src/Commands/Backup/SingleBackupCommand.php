<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

abstract class SingleBackupCommand extends BackupCommand
{

    /**
     * @param $site_env
     * @param array $options
     * @return Backup
     * @throws TerminusNotFoundException
     */
    protected function getBackup($site_env, array $options = ['file' => null, 'element' => 'all',])
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
        return $backup;
    }
}
