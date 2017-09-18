<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\WorkflowOperation;

/**
 * Class WorkflowOperations
 * @package Pantheon\Terminus\Collections
 */
class WorkflowOperations extends FetchlessCollection
{
    public static $pretty_name = 'workflow operations';
    /**
     * @var string
     */
    protected $collected_class = WorkflowOperation::class;
}
