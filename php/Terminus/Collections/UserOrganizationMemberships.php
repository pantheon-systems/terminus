<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusNotFoundException;

class UserOrganizationMemberships extends TerminusCollection
{
  /**
   * @var User
   */
    public $user;
  /**
   * @var string
   */
    protected $collected_class = 'Terminus\Models\UserOrganizationMembership';
  /**
   * @var boolean
   */
    protected $paged = true;

  /**
   * Object constructor
   *
   * @param array $options Options with which to configure this collection
   */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->user = $options['user'];
        $this->url  = "users/{$this->user->id}/memberships/organizations";
    }

    /**
     * Retrieves a member by either by the membership ID, the member organization ID, or the member organization name
     *
     * @param string $id Either a membership or member-org UUID or a member-org name
     * @return UserOrganizationMembership
     * @throws TerminusNotFoundException
     */
    public function get($id)
    {
        $models = $this->getMembers();
        if (isset($models[$id])) {
            return $models[$id];
        }
        foreach ($models as $model) {
            $organization = $model->organization;
            if (in_array($id, [$organization->get('profile')->name, $organization->id,])) {
                return $model;
            }
        }
        throw new TerminusNotFoundException(
            'An organizational member identified by "{id}" could not be found.',
            compact('id')
        );
    }
}
