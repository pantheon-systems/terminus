<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Profile;

/**
 * Class ProfileTrait
 * @package Pantheon\Terminus\Friends
 */
trait ProfileTrait
{
    /**
     * @var Profile
     */
    private $profile;

    /**
     * @return Profile Returns a Profile-type object
     */
    public function getProfile()
    {
        if (empty($this->profile)) {
            $this->setProfile($this->getContainer()->get(Profile::class, [$this->get('profile'),]));
        }
        return $this->profile;
    }

    /**
     * @param Profile $profile
     */
    public function setProfile(Profile $profile)
    {
        $this->profile = $profile;
    }
}
