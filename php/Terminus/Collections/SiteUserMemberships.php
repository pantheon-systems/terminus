<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusNotFoundException;

class SiteUserMemberships extends TerminusCollection
{
    /**
     * @var Site
     */
    public $site;
    /**
     * @var string
     */
    protected $collected_class = 'Terminus\Models\SiteUserMembership';
    /**
     * @var boolean
     */
    protected $paged = true;

    /**
     * Object constructor
     *
     * @param array $options Options to set as $this->key
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->site = $options['site'];
        $this->url = "sites/{$this->site->id}/memberships/users";
    }

    /**
     * Adds this user as a member to the site
     *
     * @param string $email Email of team member to add
     * @param string $role  Role to assign to the new user
     * @return Workflow
     **/
    public function create($email, $role)
    {
        $workflow = $this->site->workflows->create(
            'add_site_user_membership',
            ['params' => ['user_email' => $email, 'role' => $role,],]
        );
        return $workflow;
    }

    /**
     * Retrieves the membership of the given UUID or email
     *
     * @param string $id UUID or email of desired user
     * @return SiteUserMembership
     * @throws TerminusNotFoundException
     */
    public function get($id)
    {
        $models = $this->getMembers();
        if (isset($models[$id])) {
            return $models[$id];
        }
        foreach ($models as $model) {
            $user_data = $model->get('user');
            if (in_array($id, [$user_data->email, $user_data->profile->full_name])) {
                return $model;
            }
        }
        throw new TerminusNotFoundException('Cannot find site user with the name "{id}"', compact('id'));
    }
}
