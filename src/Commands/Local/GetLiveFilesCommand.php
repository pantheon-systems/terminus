<?php

namespace Pantheon\Terminus\Commands\Local;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
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
class GetLiveFilesCommand extends TerminusCommand implements
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
    use LocalCopiesTrait;

    /**
     *  Create new backup of your live site FILES folder and download to $HOME/pantheon-local-copies/{Site}/db
     *
     * @authorize
     *
     * @command local:getLiveFiles
     * @aliases lf
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
    public function downloadLiveFilesBackup($site, $options = ['overwrite' => false])
    {
        $siteData = $site;
        if (!$siteData instanceof Site) {
            $siteData = $this->getSite($site);
            if (!$siteData instanceof Site) {
                throw new TerminusException(
                    "Cannot find site with the ID: {site}",
                    ["site" => $site]
                );
            }
        }
        $liveEnv = $siteData
            ->getEnvironments()
            ->get('live');
        $files_folder = $this->getLocalCopiesFolder() . DIRECTORY_SEPARATOR . "files";
        $files_local_filename =  sprintf(
            '%s%s%s-files.tgz',
            $files_folder,
            DIRECTORY_SEPARATOR,
            $siteData->getName()
        );
        if (!is_dir($files_folder)) {
            mkdir($files_folder);
            if (!is_dir($files_folder)) {
                throw new TerminusException(
                    "Cannot create {path}",
                    ['path' => $files_folder]
                );
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
        $this->logger->notice(
            "===> Creating Live Files folder Backup for Site: {name}",
            ['name' => $liveEnv->getName()]
        );
        $this->logger->emergency(
            "Depending on how big your files directory is, this could take a while...."
        );
        $backupWorkflow = $backups->create(['element' => ['files'] ]);
        if (!$backupWorkflow instanceof Workflow) {
            throw new TerminusException("Cannot initiate backup workflow.");
        }
        $this->processWorkflow($backupWorkflow);
        if (!$backupWorkflow->isSuccessful()) {
            throw new TerminusProcessException("Backup Workflow Failed.");
        }
        $backups->fetch();
        $files_backups = $backups->getBackupsByElement('files');
        $lastBackup = reset($files_backups);
        $this->logger->notice(
            "===> Downloading db backup of {site} to {folder}.",
            ["site" => $liveEnv->getName(), 'folder' => $files_folder]
        );
        $this->request()->download(
            $lastBackup->getArchiveURL(),
            $files_local_filename
        );
        if (!is_file($files_local_filename)) {
            throw new TerminusException("Cannot download backup file.");
        }
        $this->logger->notice("Files Backup Downloaded to: {path}", ["path" => $files_local_filename]);
        return $files_local_filename;
    }
}
