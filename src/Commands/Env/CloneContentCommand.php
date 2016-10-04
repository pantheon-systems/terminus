<?php
namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\CloneCommand;

class CloneContentCommand extends CloneCommand
{

    /**
     * Clones content from one environment to another.
     *
     * @command env:clone-content
     *
     * @usage terminus env:clone-content my-site.live dev
     *   Clones the files and database from the live environment of the 'my-site'
     *   site to the development environment of the 'my-site' site.
     *
     * @param string $originSite The origin site/env to clone content from.
     * @param string $targetEnv The target environment to clone content to.
     *
     * @param array $options
     *
     * @option bool $db-only
     * @option bool $files-only
     */
    public function cloneContent($originSite, $targetEnv, array $options = [
        'db-only' => false,
        'files-only' => false,
    ])
    {
        $this->invokeClone($originSite, $targetEnv, $options);
    }
}
