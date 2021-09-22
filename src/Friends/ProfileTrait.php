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
            $nickname = \uniqid(__FUNCTION__ . "-");
            if (!empty($this->attributes->profile)) {
                $this->getContainer()->add($nickname, new Profile($this->attributes->profile))
                    ->addArgument([$this->get('profile')]);
            } else {
                $this->getContainer()->add($nickname, Profile::class)
                    ->addArgument([$this->get('profile')]);
            }
            $profile = $this->getContainer()->get($nickname);
            $this->setProfile($profile);
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
