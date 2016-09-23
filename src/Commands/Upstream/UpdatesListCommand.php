<?php

namespace Pantheon\Terminus\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Terminus\Exceptions\TerminusException;

class UpdatesListCommand extends UpstreamCommand
{
    /**
     * Lists all of the available upstream updates for a given site
     *
     * @authorized
     *
     * @name upstream :updates:list
     * @aliases upstream-updates upstream:updates
     *
     * @param string $site_id Name of the site for which to check for updates.
     *
     * @return RowsOfFields
     *
     * @field-labels
     *   hash: Commit ID
     *   datetime: Timestamp
     *   message: Message
     *   author: Author
     *
     * @throws TerminusException
     *
     * @usage terminus upstream:updates:list <site-name>
     *   Lists the available updates for the site called <site-name>
     */
    public function listUpstreamUpdates($site_id)
    {
        $data = [];
        foreach ($this->getUpstreamUpdatesLog($site_id) as $commit) {
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
