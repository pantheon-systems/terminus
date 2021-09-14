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
class CloneCommand extends TerminusCommand implements SiteAwareInterface, ConfigAwareInterface
{
    use SiteAwareTrait;
    use ConfigAwareTrait;
    use CommandExecutorTrait;

    /**
     *  CLone a copy of the site code into $HOME/pantheon-local-copies
     *
     * @authorize
     *
     * @command local:clone
     * @aliases lc
     *
     * @param string $site Site
     *
     * @usage <site> Clone's a local copy into "$HOME/pantheon-local-copies"
     * @return string
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     */
    public function clone($site) : string
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

        $env = $siteData->getEnvironments()->get('dev');

        $clone_path = $siteData->getLocalCopyDir();
        $connection =  $env->connectionInfo();

        if (!is_dir($clone_path . DIRECTORY_SEPARATOR . ".git")) {
            $this->execute(
                "git clone %s %s",
                [$connection['git_url'], $clone_path]
            );
        }
        if (!is_dir($clone_path . DIRECTORY_SEPARATOR . ".git")) {
            throw new TerminusException(
                "Clone from Pantheon's Development repository failed.",
                ['folder' => $clone_path]
            );
        }
        return $clone_path;
    }
}
