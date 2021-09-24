<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Class ListCommand.
 *
 * @package Pantheon\Terminus\Commands\Upstream\Updates
 */
class ListCommand extends UpdatesCommand
{
    /**
     * Displays a cached list of new code commits available from the upstream for a site development environment.
     * Note: To refresh the cache you will need to run site:upstream:clear-cache before running this command.
     *
     * @authorize
     * @filter-output
     *
     * @command upstream:updates:list
     *
     * @field-labels
     *     hash: Commit ID
     *     datetime: Timestamp
     *     message: Message
     *     author: Author
     * @param string $site_env Site & development environment
     *
     * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
     *
     * @usage <site>.<env> Displays a list of new code commits available from the upstream for <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function listUpstreamUpdates($site_env)
    {
        $env = $this->getEnv($site_env);

        $upstreamUpdatesLog = [];
        foreach ($this->getUpstreamUpdatesLog($env) as $commit) {
            $upstreamUpdatesLog[] = [
                'hash' => $commit->hash,
                'datetime' => $commit->datetime,
                'message' => $commit->message,
                'author' => $commit->author,
            ];
        }

        usort(
            $upstreamUpdatesLog,
            function ($a, $b) {
                if (strtotime($a['datetime']) === strtotime($b['datetime'])) {
                    return 0;
                }
                return (strtotime($a['datetime']) > strtotime($b['datetime'])) ? -1 : 1;
            }
        );

        if (empty($upstreamUpdatesLog)) {
            $this->log()->warning('There are no available updates for this site.');
        }

        // Return the output data.
        return new RowsOfFields($upstreamUpdatesLog);
    }
}
