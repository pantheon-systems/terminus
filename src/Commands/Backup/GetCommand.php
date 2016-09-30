<?php
/**
 * @file
 * Contains Pantheon\Terminus\Commands\Backup\GetCommand
 */

namespace Pantheon\Terminus\Commands\Backup;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Collections\Sites;
use Terminus\Collections\Backups;
use Terminus\Exceptions\TerminusNotFoundException;
use Terminus\Models\Environment;

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
     *
     * @return string
     *
     * @example terminus backup:get awesome-site.dev awesome-site_dev_2016-08-18T23-16-20_UTC_code.tar.gz
     * @example terminus backup:get awesome-site.dev code
     *
     */
    public function gotBackup(
        $site_env,
        $file_or_element
    ) {
        list($site, $env) = $this->getSiteEnv($site_env, 'dev');

        if (in_array($file_or_element, Backups::getValidElements())) {
            if ($file_or_element == 'db') {
                $backup_element = 'database';
            } else {
                $backup_element = $file_or_element;
            }

            $backups = $env->backups->getFinishedBackups($backup_element);
            if (empty($backups)) {
                throw new TerminusNotFoundException(
                    "No backups available. Create one with `terminus backup:create {site}.{env}`",
                    [
                        'site' => $site->get('name'),
                        'env'  => $env->id
                    ],
                    1
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
