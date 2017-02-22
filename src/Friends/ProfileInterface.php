<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Profile;

/**
 * Interface ProfileInterface
 * @package Pantheon\Terminus\Friends
 */
interface ProfileInterface
{
    /**
     * @return Profile Returns a Profile-type object
     */
    public function getProfile();

    /**
     * @param Profile $profile
     */
    public function setProfile(Profile $profile);
}
