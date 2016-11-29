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
     * Fetch the download URL for a specific backup or latest backup
     *
     * @authorize
     *
     * @command backup:get
     *
     * @param string $site_env Site & environment to deploy to, in the form `site-name.env`.
     * @option string $file [filename.tgz] Name of the backup archive file
     * @option string $element [code|files|database|db] Specify an element to back up
     * @throws TerminusNotFoundException
     *
     * @usage terminus backup:get <site>.<env>
     *     Returns the URL for the most recent backup of any type in the <env> environment of <site>
     * @usage terminus backup:get awesome-site.dev --file=2016-08-18T23-16-20_UTC_code.tar.gz
     *     Returns the URL for the backup with the specified archive file name in the <env> environment of <site>
     * @usage terminus backup:get awesome-site.dev --element=code
     *     Returns the URL for the most recent code backup in the <env> environment of <site>
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
