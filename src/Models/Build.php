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
    protected function parseAttributes($data)
    {
        return [
            'id' => $data->id,
            'status' => $data->status,
            'branch' => $data->environment->branch,
            'commit' => $data->commit ?? '',
            'created' => $data->created,
        ];
    }
}
