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
 * @name DockerizeCommand
 * Class CloneCommand
 * @package Pantheon\Terminus\Commands\Local
 */
class DockerizeCommand extends TerminusCommand implements
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
     *  Create new backup of your live site db and download to $HOME/pantheon-local-copies/{Site}/db
     *
     * @authorize
     *
     * @command local:dockerize
     * @aliases ldz
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
    public function dockerize($site, $options = ['overwrite' => false])
    {
        $siteData = $site;

        if (!$siteData instanceof Site) {
            $siteData = $this->fetchSite($site);
            if (!$siteData instanceof Site) {
                throw new TerminusException(
                    'Cannot find site with the ID: {site}',
                    ['site' => $site]
                );
            }
        }
        $env = $siteData->getEnvironments()->get('dev');
        $clone_path = $siteData->getLocalCopyDir();
        $connection = $env->connectionInfo();
        if (!is_dir($clone_path . DIRECTORY_SEPARATOR . '.git')) {
            $this->execute(
                'git clone %s %s',
                [$connection['git_url'], $clone_path]
            );
        }
        copy("./templates/localdev/.envrc", $clone_path . "/.envrc");
        copy('./templates/localdev/docker-compose.yml', $clone_path . '/docker-compose.yml');
        copy('./templates/localdev/RoboFile.php', $clone_path . '/RoboFile.php');
        copy('./templates/localdev/settings.local.php', $clone_path . '/web/sites/default/settings.local.php');
        copy('./Brewfile', $clone_path . '/Brewfile');
        cd($clone_path);
        $this->execute("direnv allow");
        $this->execute("composer require consolidation/robo");
        $this->execute('composer update -W');
        $this->execute('vendor/bin/robo docker:up');
    }
}
