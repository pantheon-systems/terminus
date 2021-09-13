<?php

namespace Pantheon\Terminus\Commands\Local;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Friends\LocalCopiesTrait;
use Pantheon\Terminus\Helpers\Traits\CommandExecutorTrait;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class GetLiveFilesCommand.
 *
 * @package Pantheon\Terminus\Commands\Local
 */
class GetLiveFilesCommand extends TerminusCommand implements
    SiteAwareInterface,
    RequestAwareInterface
{
    use SiteAwareTrait;
    use CommandExecutorTrait;
    use WorkflowProcessingTrait;
    use RequestAwareTrait;
    use LocalCopiesTrait;

    /**
     * Create new backup of your live site FILES folder and download to $HOME/pantheon-local-copies/files
     *
     * @authorize
     *
     * @command local:getLiveFiles
     * @aliases lf
     *
     * @param string|Site $site
     * @option bool $overwrite Overwrite existing file
     *
     * @usage <site> Create new backup of your live site and download to $HOME/pantheon-local-copies/files
     * @usage <site> --overwrite Same + overwrite existing file
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function downloadLiveFilesBackup($site, $options = ['overwrite' => false])
    {
        if (!$site instanceof Site) {
            $site = $this->getSite($site);
        }
        /** @var \Pantheon\Terminus\Models\Environment $liveEnv */
        $liveEnv = $site
            ->getEnvironments()
            ->get('live');

        $backups = $liveEnv->getBackups();
        $this->logger->notice(
            '===> Creating files backup for {site}',
            ['site' => $liveEnv->getName()]
        );
        $this->logger->notice('Depending on how large your "files" directory is, this could take a while.');
        $backupWorkflow = $backups->create(['element' => ['files']]);
        $this->processWorkflow($backupWorkflow);
        if (!$backupWorkflow->isSuccessful()) {
            throw new TerminusProcessException('Backup workflow failed.');
        }
        $backups->fetch();
        $filesBackups = $backups->getBackupsByElement('files');
        $lastBackup = reset($filesBackups);
        $this->logger->notice(
            '===> Downloading files backup of {site} into {dir}.',
            ['site' => $liveEnv->getName(), 'dir' => $this->getLocalCopiesFilesDir()]
        );

        $filesBackupPath = sprintf(
            '%s%s%s-files.tgz',
            $this->getLocalCopiesFilesDir(),
            DIRECTORY_SEPARATOR,
            $site->getName()
        );
        $this->request()->download(
            $lastBackup->getArchiveURL(),
            $filesBackupPath,
            $options['overwrite']
        );
        $this->logger->notice('Files backup downloaded into: {path}', ['path' => $filesBackupPath]);

        return $filesBackupPath;
    }
}
