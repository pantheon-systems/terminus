<?php

namespace Pantheon\Terminus\Commands\NodeLogs;

/**
 * Fetch the build log for a site.
 */
class NodeLogsBuildGetCommand extends NodeLogsBaseCommand
{
    /**
     * Print the build log.
     *
     * @authorize
     *
     * @command node:logs:build:get
     * @alias nlbg
     *
     * @param string $site_env Site & environment in the format `site-name.env` (only Test or Live environment)
     * @param string $build_id Build ID
     */
    public function buildGet($site_env, $build_id)
    {
        $this->requireSiteIsNotFrozen($site_env);

        $site = $this->getSiteById($site_env);

        $text = $this->getFromUrl(
            sprintf('/api/sites/%s/build/%s/log', $site->id, $build_id)
        );

        if ($text) {
            $this->output()->writeln($text);
        } else {
            $this->output()->writeln('This build is queued. Logs will display once they are available.');
        }
    }
}
