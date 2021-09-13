<?php

namespace Pantheon\Terminus\Commands\Local;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Friends\LocalCopiesTrait;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class GetLiveDBCommand.
 *
 * @package Pantheon\Terminus\Commands\Local
 */
class GetLiveDBCommand extends TerminusCommand implements
    SiteAwareInterface,
    RequestAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;
    use RequestAwareTrait;
    use LocalCopiesTrait;

    /**
     * Create new backup of your live site db and download to $HOME/pantheon-local-copies/{Site}/db
     *
     * @authorize
     *
     * @command local:getLiveDB
     * @aliases ldb
     *
     * @param string $site Site
     * @option bool $overwrite Overwrite existing file
     *
     * @usage <site> Create new backup of your live site and download to $HOME/pantheon-local-copies/{Site}/db
     * @usage <site> --overwrite Same + overwrite existing file
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function downloadLiveDbBackup($site, $options = ['overwrite' => false])
    {
        $siteData = $site;
        if (!$siteData instanceof Site) {
            $siteData = $this->getSite($site);
            if (!$siteData instanceof Site) {
                throw new TerminusException(
                    'Cannot find site with the ID: {site}',
                    ['site' => $site]
                );
            }
        }
        $liveEnv = $siteData
            ->getEnvironments()
            ->get('live');
        $db_folder = $this->getLocalCopiesFolder() . DIRECTORY_SEPARATOR . 'db';
        $db_local_filename =  sprintf(
            '%s%s%s-db.tgz',
            $db_folder,
            DIRECTORY_SEPARATOR,
            $siteData->getName()
        );
        if (!is_dir($db_folder)) {
            mkdir($db_folder);
            if (!is_dir($db_folder)) {
                throw new TerminusException(
                    'Cannot create {path}:',
                    ['path' => $db_folder]
                );
            }
        }

        if (!$liveEnv instanceof Environment) {
            throw new TerminusException("Cannot locate site's Live Environment.");
        }
        $this->logger->notice(
            '===> Fetching the backup catalog for {site}.',
            ['site' => $liveEnv->getName()]
        );
        $backups = $liveEnv->getBackups();
        $backups->fetch();
        $db_backups = $backups->getBackupsByElement('database');
        if (count($db_backups) === 0) {
            $this->logger->notice(
                '===> Creating database backup for {site}',
                ['site' => $liveEnv->getName()]
            );
            $backupWorkflow = $backups->create(['element' => ['database']]);
            if ($backupWorkflow instanceof Workflow) {
                $this->processWorkflow($backupWorkflow);
                if (!$backupWorkflow->isSuccessful()) {
                    throw new TerminusProcessException('Backup Workflow Failed.');
                }
            }
            $backups->fetch();
            $db_backups = $backups->getBackupsByElement('database');
        }
        $lastBackup = reset($db_backups);
        $this->logger->notice(
            '===> Downloading db backup of {site} to {folder}.',
            ['site' => $liveEnv->getName(), 'folder' => $db_folder]
        );
        $this->request()->download(
            $lastBackup->getArchiveURL(),
            $db_local_filename,
            $options['overwrite']
        );
        $this->logger->notice('DB Backup Downloaded to: {path}', ['path' => $db_local_filename]);

        return $db_local_filename;
    }
}
