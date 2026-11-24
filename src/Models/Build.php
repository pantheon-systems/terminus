<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class Build
 *
 * @package Pantheon\Terminus\Models
 */
class Build extends TerminusModel
{
    public const PRETTY_NAME = 'build';

    /**
     * Modify response data between fetch and assignment
     *
     * @param object $data attributes received from API response
     *
     * @return object $data
     */
    protected function parseAttributes(object $data): object
    {
        return (object) [
            'id' => $data->id,
            'status' => $data->status,
            'branch' => $data->environment->branch,
            'commit' => $data->commit ?? '',
            'deployed' => !empty($data->release_id) ? 'true' : 'false',
            'active' => !empty($data->active) ? 'true' : 'false',
            'rollbackable' => !empty($data->rollbackable) ? 'true' : 'false',
            'created' => $data->created,
        ];
    }
}
