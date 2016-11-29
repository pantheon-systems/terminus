<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\CloneCommand;

/**
 * Class CloneContentCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class CloneContentCommand extends CloneCommand
{
    /**
     * Clone content from one environment to another
     *
     * @authorize
     *
     * @command env:clone-content
     *
     * @param string $site_env The origin site/environment to clone content from
     * @param string $target_env The target environment to clone content to
     * @option bool $db-only Use to only clone the database
     * @option bool $files-only Use to only clone the files
     *
     * @usage terminus env:clone-content <site>.<env> <other_env>
     *   Clones the files and database from the <env> environment of <site> to its <other_env> environment
     * @usage terminus env:clone-content <site>.<env> <other_env> --db-only
     *   Clones the database from the <env> environment of <site> to its <other_env> environment
     * @usage terminus env:clone-content <site>.<env> <other_env> --files-only
     *   Clones the files from the <env> environment of <site> to its <other_env> environment
     */
    public function cloneContent($originSite, $targetEnv, array $options = ['db-only' => false, 'files-only' => false,])
    {
        $this->invokeClone($originSite, $targetEnv, $options);
    }
}
