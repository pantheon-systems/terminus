<?php

namespace Pantheon\Terminus\Commands\Local;

use Consolidation\OutputFormatters\StructuredData\AbstractStructuredList;
use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Friends\LocalCopiesTrait;
use Pantheon\Terminus\Helpers\Traits\CommandExecutorTrait;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Robo\Contract\ConfigAwareInterface;

/**
 * @name LocalCommands
 * Class CloneCommand
 * @package Pantheon\Terminus\Commands\Local
 */
class CommitAndPushCommand extends TerminusCommand implements SiteAwareInterface, ConfigAwareInterface
{
    use SiteAwareTrait;
    use ConfigAwareTrait;
    use CommandExecutorTrait;

    /**
     * Commit local changes to remote repository.
     *
     * @authorize
     *
     * @command local:commitAndPush
     * @aliases lcp
     *
     * @param string $site Site
     *
     * @usage <site> Clone's a local copy into "$HOME/pantheon-local-copies"
     *@return string
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     */
    public function commitAndPushCommand($site) : string
    {
        $siteData = $site;
        if (!$siteData instanceof Site) {
            $siteData = $this->getSiteById($site);
            if (!$siteData instanceof Site) {
                throw new TerminusException(
                    "Cannot find site with the ID: {site}",
                    ["site" => $site]
                );
            }
        }
        $git = new \CzProject\GitPhp\Git();
        $repo = $git->open($siteData->getLocalCopyDir());
        $repo->addAllChanges();
        $repo->commit('changes committed from terminus');
        $repo->push('origin');
    }
}
