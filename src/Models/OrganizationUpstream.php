<?php

namespace Pantheon\Terminus\Models;

/**
 * Class OrganizationUpstream
 * @package Pantheon\Terminus\Models
 */
class OrganizationUpstream extends TerminusModel
{
    public static $pretty_name = 'upstream';

    /**
     * @return string[]
     */
    public function getReferences()
    {
        return [$this->id, $this->get('label'), $this->get('machine_name'),];
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        $data = (array)$this->attributes;
        $data['organization'] = $this->collection->getOrganization()->getLabel();
        return $data;
    }
}
