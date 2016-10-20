<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusNotFoundException;

class GetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Fetch the download URL for a specific backup or latest backup
     *
     * @authorized
     *
     * @command backup:get
     *
     * @param string $site_env Site & environment to deploy to, in the form `site-name.env`.
     * @param string $file_or_element [filename.tgz|code|files|database|db] Filename or backup type
     * @return string
     *
     * @usage terminus backup:get awesome-site.dev awesome-site_dev_2016-08-18T23-16-20_UTC_code.tar.gz
     *     Returns the URL for the backup with the specified archive file name
     * @usage terminus backup:get awesome-site.dev code
     *     Returns the URL for the most recent code backup
     * @usage terminus backup:get awesome-site.dev
     *     Returns the URL for the most recent code backup of any type
     */
    public function getBackup($site_env, $file_or_element = null)
    {
        list($site, $env) = $this->getSiteEnv($site_env);

        if (in_array($file_or_element, $env->backups->getValidElements())) {
            if ($file_or_element == 'db') {
                $backup_element = 'database';
            } else {
                $backup_element = $file_or_element;
            }

            $backups = $env->backups->getFinishedBackups($backup_element);
            if (empty($backups)) {
                throw new TerminusNotFoundException(
                    'No backups available. Create one with `terminus backup:create {site}.{env}`',
                    ['site' => $site->get('name'), 'env' => $env->id,]
                );
            } else {
                $backup = array_shift($backups);
            }
        } else {
            $file = $file_or_element;
            $backup = $env->backups->getBackupByFileName($file);
        }

        $url = $backup->getUrl();
        return $url;
    }
}
