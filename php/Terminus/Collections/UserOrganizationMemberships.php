<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusException;

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
}
