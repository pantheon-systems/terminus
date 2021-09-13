<?php

namespace Pantheon\Terminus\Commands\Local;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Friends\LocalCopiesTrait;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

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
     * @param string|Site $site Site
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
        if (!$site instanceof Site) {
            $site = $this->getSite($site);
        }

        /** @var \Pantheon\Terminus\Models\Environment $liveEnv */
        $liveEnv = $site
            ->getEnvironments()
            ->get('live');

        $this->logger->notice(
            '===> Fetching the backup catalog for {site}.',
            ['site' => $liveEnv->getName()]
        );
        $backups = $liveEnv->getBackups();
        $backups->fetch();
        $dbBackups = $backups->getBackupsByElement('database');
        if (count($dbBackups) === 0) {
            $this->logger->notice(
                '===> Creating database backup for {site}',
                ['site' => $liveEnv->getName()]
            );
            $backupWorkflow = $backups->create(['element' => ['database']]);
            if ($backupWorkflow instanceof Workflow) {
                $this->processWorkflow($backupWorkflow);
                if (!$backupWorkflow->isSuccessful()) {
                    throw new TerminusProcessException('Backup workflow failed.');
                }
            }
            $backups->fetch();
            $dbBackups = $backups->getBackupsByElement('database');
        }
        $lastBackup = reset($dbBackups);
        $this->logger->notice(
            '===> Downloading db backup of {site} to {dir}.',
            ['site' => $liveEnv->getName(), 'dir' => $this->getLocalCopiesDbDir()]
        );

        $dbLocalFilename = sprintf(
            '%s%s%s-db.tgz',
            $this->getLocalCopiesDbDir(),
            DIRECTORY_SEPARATOR,
            $site->getName()
        );
        $this->request()->download(
            $lastBackup->getArchiveURL(),
            $dbLocalFilename,
            $options['overwrite']
        );
        $this->logger->notice('Database backup downloaded into: {path}', ['path' => $dbLocalFilename]);

        return $dbLocalFilename;
    }
}
