<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\WorkflowOperation;

/**
 * Class WorkflowOperations
 * @package Pantheon\Terminus\Collections
 */
class WorkflowOperations extends TerminusCollection
{
    public const PRETTY_NAME = 'workflow operations';
    /**
     * @var string
     */
    protected string $collected_class = WorkflowOperation::class;
}
