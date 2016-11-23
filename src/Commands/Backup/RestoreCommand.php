<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

class RestoreCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Restores a specific backup or a latest backup
     *
     * @authorized
     *
     * @command backup:restore
     *
     * @param string $site_env Site & environment to deploy to, in the form `site-name.env`.
     * @option string $file [filename.tgz] Name of the backup archive file
     * @option string $element [code|files|database|db] Backup type
     * @throws TerminusException
     *
     * @usage terminus backup:restore awesome-site.dev
     *     Restores the most recent backup of any type to the dev environment of awesome-site
     * @usage terminus backup:restore awesome-site.dev --file=awesome-site_dev_2016-08-18T23-16-20_UTC_code.tar.gz
     *     Restores backup with the specified archive file name to awesome-site's dev environment
     * @usage terminus backup:restore awesome-site.dev --element=code
     *     Restores the most recent code backup for the dev environment of awesome-site
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
