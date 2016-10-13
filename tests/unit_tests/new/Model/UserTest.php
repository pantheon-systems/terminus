<?php


namespace Pantheon\Terminus\UnitTests\Model;

use League\Container\Container;
use Pantheon\Terminus\Collections\Instruments;
use Pantheon\Terminus\Collections\MachineTokens;
use Pantheon\Terminus\Collections\SshKeys;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Collections\UserSiteMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\User;
use Robo\Collection\Collection;

class UserTest extends ModelTestCase
{
    /**
     * @var User
     */
    protected $user;
    protected $user_data;

    public function setUp()
    {
        parent::setUp();

        $this->user_data = [
            'id' => '123',
            'email' => 'dev@example.com',
            'profile' => (object)[
                'firstname' => 'Peter',
                'lastname' => 'Pantheor',
            ]
        ];
        $this->user = new User((object)$this->user_data);
        $this->user->setRequest($this->request);
    }

    public function testGetAliases()
    {

        $aliases = ['foo', 'bar'];
        $this->request->expects($this->once())
            ->method('request')
            ->with("users/123/drush_aliases", ['method' => 'get'])
            ->willReturn(['data' => (object)['drush_aliases' => $aliases]]);

        $out = $this->user->getAliases();
        $this->assertEquals($aliases, $out);
        // Confirm that it returns the same output twice without calling to the API twice.
        $this->assertEquals($aliases, $this->user->getAliases());
    }

    public function testGetSubCollections()
    {
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $classes = [
            Instruments::class,
            MachineTokens::class,
            UserOrganizationMemberships::class,
            UserSiteMemberships::class,
            SshKeys::class,
            Workflows::class
        ];
        foreach ($classes as $i => $class) {
            $container->expects($this->at($i))
                ->method('get')
                ->with($class, [['user' => $this->user]])
                ->willReturn(new $class(['user' => $this->user]));
        }

        $this->user->setContainer($container);

        $this->user->getInstruments();
        $this->user->getMachineTokens();
        $this->user->getOrgMemberships();
        $this->user->getSiteMemberships();
        $this->user->getSshKeys();
        $this->user->getWorkflows();
    }

    public function testSerialize()
    {
        $expected = array_merge($this->user_data, (array)$this->user_data['profile']);
        unset($expected['profile']);

        $data = $this->user->serialize();
        $this->assertEquals($expected, $data);
    }

    public function testGetSites()
    {
        $memberships = [
            (object)[
                'id' => '1',
                'site' => (object)[
                    'id' => 'site1',
                    'other' => 'abc'
                ]
            ],
            (object)[
                'id' => '2',
                'site' => (object)[
                    'id' => 'site2',
                    'other' => 'cdf'
                ]
            ]
        ];
        $sites = [
            'site1' => $memberships[0]->site,
            'site2' => $memberships[1]->site
        ];

        $sitememberships = $this->getMockBuilder(UserSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sitememberships->expects($this->once())
            ->method('all')
            ->willReturn($memberships);

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(UserSiteMemberships::class, [['user' => $this->user]])
            ->willReturn($sitememberships);

        $this->user->setContainer($container);

        $this->assertEquals($sites, $this->user->getSites());
    }

    public function testGetOrgs()
    {
        $memberships = [
            (object)[
                'id' => '1',
                'organization' => (object)[
                    'id' => 'org1',
                    'other' => 'abc'
                ]
            ],
            (object)[
                'id' => '2',
                'organization' => (object)[
                    'id' => 'org2',
                    'other' => 'cdf'
                ]
            ]
        ];
        $orgs = [
            'org1' => $memberships[0]->organization,
            'org2' => $memberships[1]->organization
        ];

        $orgmemberships = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orgmemberships ->expects($this->once())
            ->method('all')
            ->willReturn($memberships);

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(UserOrganizationMemberships::class, [['user' => $this->user]])
            ->willReturn($orgmemberships);

        $this->user->setContainer($container);

        $this->assertEquals($orgs, $this->user->getOrganizations());
    }
}
