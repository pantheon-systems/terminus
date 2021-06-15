<?php

namespace Pantheon\Terminus\Commands\Local;

use Consolidation\OutputFormatters\StructuredData\AbstractStructuredList;
use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Collections\Backups;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Friends\LocalCopiesTrait;
use Pantheon\Terminus\Helpers\Traits\CommandExecutorTrait;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * @name LocalCommands
 * Class CloneCommand
 * @package Pantheon\Terminus\Commands\Local
 */
class DownloadLiveDbBackupCommand extends TerminusCommand implements
    SiteAwareInterface,
    ConfigAwareInterface,
    RequestAwareInterface
{
    use SiteAwareTrait;
    use ConfigAwareTrait;
    use CommandExecutorTrait;
    use WorkflowProcessingTrait;
    use LoggerAwareTrait;
    use RequestAwareTrait;

    /**
     *  Create new backup of your live site's db and download to $HOME/pantheon-local-copies/{Site}/db
     *
     * @authorize
     *
     * @command local:downloadLiveDbBackup
     * @aliases ldb
     *
     * @param string $site Site
     * @option bool $overwrite Overwrite existing file
     *
     * @usage <site> Create new backup of your live site and download to $HOME/pantheon-local-copies/{Site}/db
     * @usage <site> --overwrite Same + overwrite existing file
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     */
    public function downloadLiveDbBackup($site, $options = ['overwrite' => false])
    {
        $siteData = $site;
        if (!$siteData instanceof Site) {
            $siteData = $this->getSite($site);
        }
        $liveEnv = $siteData
            ->getEnvironments()
            ->get('live');
        $db_folder = $siteData->getLocalCopyFolder() . DIRECTORY_SEPARATOR . "db";
        $db_local_filename =  sprintf(
            '%s%s%s-db.tgz',
            $db_folder,
            DIRECTORY_SEPARATOR,
            $siteData->getName()
        );
        if (!is_dir($db_folder)) {
            mkdir($db_folder);
            // TODO: update .gitignore
            if (!is_dir($db_folder)) {
                throw new TerminusException("Cannot create 'files' folder inside local copy of site");
            }
        }

        if (!$liveEnv instanceof Environment) {
            throw new TerminusException("Cannot locate site's Live Environment.");
        }
        $this->logger->notice(
            "===> Fetching the backup catalog for {site}.",
            ["site" => $liveEnv->getName()]
        );
        $backups = $liveEnv->getBackups();
        $backups->fetch();
        $db_backups = $backups->getBackupsByElement('database');
        if (count($db_backups) === 0) {
            $this->logger->notice(
                "===> Creating  Live Database Backup for Site: {name}",
                ['name' => $liveEnv->getName()]
            );
            $backupWorkflow = $backups->create(['element' => ['database'] ]);
            if ($backupWorkflow instanceof Workflow) {
                $this->processWorkflow($backupWorkflow);
                if (!$backupWorkflow->isSuccessful()) {
                    throw new TerminusProcessException("Backup Workflow Failed.");
                }
            }
            $backups->fetch();
            $db_backups = $backups->getBackupsByElement('database');
        }
        $lastBackup = reset($db_backups);
        $this->logger->notice(
            "===> Downloading db backup of {site} to {folder}.",
            ["site" => $liveEnv->getName(), 'folder' => $db_folder]
        );
        $this->request()->download(
            $lastBackup->getArchiveURL(),
            $db_local_filename
        );
        $this->logger->notice("DB Backup Downloaded to: {path}", ["path" => $db_local_filename]);
    }
}
