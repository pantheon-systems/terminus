<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Friends\OrganizationsInterface;
use Pantheon\Terminus\Friends\OrganizationsTrait;
use Pantheon\Terminus\Models\Upstream;

/**
 * Class Upstreams
 * @package Pantheon\Terminus\Collections
 */
class Upstreams extends UserOwnedCollection implements OrganizationsInterface
{
    use OrganizationsTrait;

    const PRETTY_NAME = 'upstreams';
    /**
     * @var string
     */
    protected $collected_class = Upstream::class;
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/upstreams';

    /**
     * Adds a model to this collection
     *
     * @param object $model_data Data to feed into attributes of new model
     * @param array $options Data to make properties of the new model
     * @return TerminusModel
     */
    public function add($model_data, array $options = [])
    {
        $model = parent::add($model_data, $options);
        if (!empty($org_id = $model_data->organization_id)) {
            try {
                $model->setOrganization($this->getOrganizationMemberships()->get($org_id)->getOrganization());
            } catch (TerminusNotFoundException $e) {
                // Do nothing
            }
        }
        return $model;
    }

    /**
     * Filters an array of Upstreams by their label
     *
     * @param string $regex Non-delimited PHP regex to filter site names by
     * @return Upstreams
     */
    public function filterByName($regex = '(.*)')
    {
        return $this->filterByRegex('label', $regex);
    }

    /**
     * @return UserOrganizationMemberships
     */
    public function getOrganizationMemberships()
    {
        return $this->getUser()->getOrganizationMemberships();
    }
}
