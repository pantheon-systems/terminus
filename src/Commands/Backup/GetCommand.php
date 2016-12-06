<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Class GetCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class GetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

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
     * @throws TerminusNotFoundException
     *
     * @usage terminus backup:get <site>.<env>
     *     Displays the URL for the most recent backup of any type in <site>'s <env> environment.
     * @usage terminus backup:get awesome-site.dev --file=2016-08-18T23-16-20_UTC_code.tar.gz
     *     Displays the URL for the backup with the specified file name in <site>'s <env> environment.
     * @usage terminus backup:get awesome-site.dev --element=code
     *     Displays the URL for the most recent code backup in <site>'s <env> environment.
     */
    public function getBackup($site_env, array $options = ['file' => null, 'element' => null,])
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

        return $backup->getUrl();
    }
}
