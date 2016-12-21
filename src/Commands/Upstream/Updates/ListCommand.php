<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Upstream\Updates
 */
class ListCommand extends UpdatesCommand
{
    /**
     * Displays a list of new code commits available from the upstream for a site's Dev environment.
     *
     * @authorize
     *
     * @command upstream:updates:list
     * @aliases upstream:updates
     *
     * @field-labels
     *     hash: Commit ID
     *     datetime: Timestamp
     *     message: Message
     *     author: Author
     * @return RowsOfFields
     *
     * @param string $site_id Site name
     *
     * @throws TerminusException
     *
     * @usage terminus upstream:updates:list <site>
     *     Displays a list of new code commits available from the upstream for <site>'s Dev environment.
     */
    public function listUpstreamUpdates($site_id)
    {
        $site = $this->getSite($site_id);
        $data = [];
        foreach ($this->getUpstreamUpdatesLog($site) as $commit) {
            $data[] = [
                'hash' => $commit->hash,
                'datetime' => $commit->datetime,
                'message' => $commit->message,
                'author' => $commit->author,
            ];
        }

        if (empty($data)) {
            $this->log()->warning("There are no available updates for this site.");
        }

        // Return the output data.
        return new RowsOfFields($data);
    }
}
