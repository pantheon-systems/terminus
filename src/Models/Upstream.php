<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Friends\OrganizationInterface;
use Pantheon\Terminus\Friends\OrganizationTrait;

/**
 * Class Upstream
 * @package Pantheon\Terminus\Models
 */
class Upstream extends TerminusModel implements OrganizationInterface
{
    use OrganizationTrait;

    const PRETTY_NAME = 'upstream';
    /**
     * @var string
     */
    protected $url = 'upstreams/{id}';

    /**
     * @return Organization|null Returns a Organization-type object
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return string[]
     */
    public function getReferences()
    {
        return [$this->id, $this->get('product_id'), $this->get('label'), $this->get('machine_name'),];
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        $data = (array)$this->attributes;
        $data['organization'] = is_null($org = $this->getOrganization()) ? null : $org->getLabel();
        return $data;
    }
}
