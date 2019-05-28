<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Friends\OrganizationInterface;
use Pantheon\Terminus\Friends\OrganizationTrait;

/**
 * Class Upstream
 * @package Pantheon\Terminus\Models
 */
class Upstream extends TerminusModel implements ContainerAwareInterface, OrganizationInterface
{
    use ContainerAwareTrait;
    use OrganizationTrait;

    const PRETTY_NAME = 'upstream';
    /**
     * @var string
     */
    protected $url = 'organizations/{org_id}/upstreams/{id}';

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
     * @return Repository
     */
    public function getRepository()
    {
        if (empty($this->repository)) {
            $this->repository = $this->getContainer()->get(Repository::class, [null, ['upstream' => $this,],]);
        }
        return $this->repository;
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
