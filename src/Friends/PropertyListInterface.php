<?php

namespace Pantheon\Terminus\Friends;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Models\TerminusModel;

/**
 * Class PropertyListInterface
 * @package Pantheon\Terminus\Friends
 */
interface PropertyListInterface
{
    /**
     * @param TerminusModel $model A model with data to return
     * @return PropertyList Returns a PropertyList-type object with rendering filters added
     */
    public function getPropertyList(TerminusModel $model);
}
