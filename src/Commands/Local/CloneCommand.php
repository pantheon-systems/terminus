<?php

namespace Pantheon\Terminus\Commands\Local;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\Traits\CommandExecutorTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class CloneCommand.
 *
 * @package Pantheon\Terminus\Commands\Local
 */
class CloneCommand extends TerminusCommand implements SiteAwareInterface, ConfigAwareInterface
{
    use SiteAwareTrait;
    use ConfigAwareTrait;
    use CommandExecutorTrait;

    /**
     * CLone a copy of the site code into $HOME/pantheon-local-copies
     *
     * @authorize
     *
     * @command local:clone
     * @aliases lc
     *
     * @param string $site_id The name or UUID of the site
     * @param array $options
     *
     * @return string
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @option site_dir Custom directory for the local copy. By default, the site name is used
     * @option override Override the local copy if exists
     *
     * @usage <site> Clone's a local copy into "$HOME/pantheon-local-copies"
     */
    public function clone(string $site_id, array $options = ['site_dir' => null, 'override' => null]): string
    {
        $site = $this->getSite($site_id);
        $env = $site->getEnvironments()->get('dev');

        $localCopyDir = $site->getLocalCopyDir($options['site_dir'] ?? null);
        $gitUrl = $env->connectionInfo()['git_url'] ?? null;
        if (null === $gitUrl) {
            throw new TerminusException('Failed to get connection Git URL');
        }

        $override = $options['override'] ?? false;
        if (is_dir($localCopyDir . DIRECTORY_SEPARATOR . '.git')) {
            if (!$override) {
                $this->logger->notice(
                    sprintf('The local copy of the site %s already exists in %s', $site->getName(), $localCopyDir)
                );

                return $localCopyDir;
            }

            $this->execute('rm -rf %s', [$localCopyDir]);
        }

        $this->execute('git clone %s %s', [$gitUrl, $localCopyDir]);

        return $localCopyDir;
    }
}
