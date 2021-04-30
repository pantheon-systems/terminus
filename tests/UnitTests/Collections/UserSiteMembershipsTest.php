<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\UserSiteMemberships;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Request\Request;

/**
 * Class UserSiteMembershipsTest
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class UserSiteMembershipsTest extends UserOwnedCollectionTest
{
    /**
     * @var string
     */
    protected $class = UserSiteMemberships::class;
    /**
     * @var string
     */
    protected $url = 'users/USERID/memberships/sites';

    /**
     * Indirectly tests TerminusCollection::fetch with a paged-data collection
     */
    public function testFetch()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder(User::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs(['id' => 'user id',])
            ->getMock();

        $request->expects($this->once())
            ->method('pagedRequest')
            ->with($this->equalTo(str_replace('USERID', '', $this->url)))
            ->willReturn(['data' => (object)[],]);

        $collection = new UserSiteMemberships(['user' => $user,]);
        $collection->setRequest($request);
        $out = $collection->fetch();
        $this->assertEquals($out, $collection);
    }
}
