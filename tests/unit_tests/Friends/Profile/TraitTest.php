<?php

namespace Pantheon\Terminus\UnitTests\Friends\Profile;

use Pantheon\Terminus\Models\Profile;

/**
 * Class TraitTest
 * Testing class for Pantheon\Terminus\Friends\ProfileTrait & Pantheon\Terminus\Friends\UserInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Profile
 */
class TraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DummyClass
     */
    protected $model;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = new DummyClass();
    }

    /**
     * Tests ProfileTrait::getProfile() and ProfileTrait::setProfile()
     */
    public function testAll()
    {
        $profile = $this->getMockBuilder(Profile::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model->setProfile($profile);
        $this->assertEquals($profile, $this->model->getProfile());
    }
}
