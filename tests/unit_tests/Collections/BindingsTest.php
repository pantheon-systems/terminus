<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\Bindings;
use Pantheon\Terminus\Models\Binding;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;

/**
 * Class BindingsTest
 * Testing class for Pantheon\Terminus\Collections\Bindings
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class BindingsTest extends CollectionTestCase
{
    /**
     * @var object
     */
    protected $bindings_data;

    public function setUp()
    {
        parent::setUp();

        $this->bindings_data = (object)[
            'd37eff55a5ec4c3daf31f17f239fd893' =>
                (object)[
                    'binding_created' => 1471385346,
                    'client' => 'fusedav-release',
                    'cluster' => 'valhalla4',
                    'comment' => 'Created by environment creation.',
                    'environment' => 'live',
                    'host' => 'remote',
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'fileserver',
                    'fqdn' => 'valhalla4.cluster.panth.io',
                ],
                '0bc33e0b7b37462cad40e081d5173721' =>
                (object)[
                    'access_key' => '6da9cc4b98554574b9d0e1fe43e57314',
                    'binding_created' => 1471385341,
                    'cached_at' => 1471563088,
                    'comment' => 'Created by environment creation.',
                    'converged_at' => 1471563088,
                    'current_commit' => 'a6a94cff8dbc3f15c93b2d3c6777aa334a476927',
                    'current_ref' => 'refs/heads/master',
                    'endpoint' => '17fbb90d-7844-404d-9e49-3b034a0cc088',
                    'endpoint_zone' => 'chios',
                    'environment' => 'dev',
                    'host' => '23.253.170.86',
                    'port' => 15015,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'appserver',
                    'ideal_host' => '23.253.170.86',
                    'ideal_fqdn' => '17fbb90d-7844-404d-9e49-3b034a0cc088.panth.io',
                    'fqdn' => '17fbb90d-7844-404d-9e49-3b034a0cc088.panth.io',
                ],
                'c31cecd9e55f474cb80b25951473688e' =>
                (object)[
                    'binding_created' => 1471385342,
                    'cached_at' => 1471595493,
                    'comment' => 'Created by environment creation.',
                    'converged_at' => 1471595493,
                    'create_site' => false,
                    'database' => 'pantheon',
                    'endpoint' => 'b18e3a08-8ba3-4c98-815a-fc18158fd314',
                    'endpoint_zone' => 'chios',
                    'environment' => 'dev',
                    'host' => '23.253.60.7',
                    'password' => 'ad7e59695d264b3782c2a9fd959d6a40',
                    'port' => 16698,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'dbserver',
                    'username' => 'pantheon',
                    'ideal_host' => '23.253.60.7',
                    'ideal_fqdn' => 'b18e3a08-8ba3-4c98-815a-fc18158fd314.panth.io',
                    'fqdn' => 'b18e3a08-8ba3-4c98-815a-fc18158fd314.panth.io',
                ],
                'b849d36fc4d5476cba555c34fbbd8c38' =>
                (object)[
                    'binding_created' => 1471385345,
                    'cached_at' => 1471527886,
                    'comment' => 'Created by environment creation.',
                    'converged_at' => 1471527886,
                    'database' => 'pantheon',
                    'endpoint' => 'aab3fa62-13e5-41dd-a8a6-f10afd142c30',
                    'endpoint_zone' => 'chios',
                    'environment' => 'test',
                    'host' => '104.130.221.57',
                    'password' => 'ac7b3205bb5b4f6884c4cddf99dfa4c3',
                    'port' => 17434,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'dbserver',
                    'username' => 'pantheon',
                    'ideal_host' => '104.130.221.57',
                    'ideal_fqdn' => 'aab3fa62-13e5-41dd-a8a6-f10afd142c30.panth.io',
                    'fqdn' => 'aab3fa62-13e5-41dd-a8a6-f10afd142c30.panth.io',
                ],
                '69189ea7f4c44ad98e5433e85ab0e76b' =>
                (object)[
                    'binding_created' => 1471385346,
                    'cached_at' => 1471594339,
                    'comment' => 'Created by environment creation.',
                    'converged_at' => 1471594339,
                    'database' => 'pantheon',
                    'endpoint' => 'a528426d-f124-44f2-b662-dd558df57b5e',
                    'endpoint_zone' => 'chios',
                    'environment' => 'live',
                    'host' => '162.242.168.67',
                    'password' => 'a7dd95d3d52f40f3806b7a307e1fe748',
                    'port' => 16569,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'dbserver',
                    'username' => 'pantheon',
                    'ideal_host' => '162.242.168.67',
                    'ideal_fqdn' => 'a528426d-f124-44f2-b662-dd558df57b5e.panth.io',
                    'fqdn' => 'a528426d-f124-44f2-b662-dd558df57b5e.panth.io',
                ],
                'ffb86439e04a4739ac49a41a7021dcfb' =>
                (object)[
                    'access_key' => '5873e31554fc419281a9991b124e2980',
                    'binding_created' => 1471385341,
                    'cached_at' => 1471602298,
                    'comment' => 'Created by environment creation.',
                    'converged_at' => 1471602298,
                    'create_site' => false,
                    'endpoint' => '098311de-5761-4036-9696-2073a04aa21b',
                    'endpoint_zone' => 'chios',
                    'environment' => 'dev',
                    'host' => '104.239.200.45',
                    'port' => 11797,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'codeserver',
                    'ideal_host' => '104.239.200.45',
                    'ideal_fqdn' => '098311de-5761-4036-9696-2073a04aa21b.panth.io',
                    'fqdn' => '098311de-5761-4036-9696-2073a04aa21b.panth.io',
                ],
                'd9eee142f9414e4eaf79e4190e433c39' =>
                (object)[
                    'access_key' => '44cd377aeb2d4b9395031ec1aa95eada',
                    'binding_created' => 1471385345,
                    'cached_at' => 1471583650,
                    'comment' => 'Created by environment creation.',
                    'converged_at' => 1471583650,
                    'current_commit' => '1fdf194d3d7a0c930a4f118e1398412765320328',
                    'current_ref' => 'refs/tags/pantheon_live_1',
                    'endpoint' => 'ece44d39-1d2f-4c12-b3f7-bc9f9241225e',
                    'endpoint_zone' => 'chios',
                    'environment' => 'live',
                    'host' => '104.130.221.23',
                    'port' => 19507,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'appserver',
                    'ideal_host' => '104.130.221.23',
                    'ideal_fqdn' => 'ece44d39-1d2f-4c12-b3f7-bc9f9241225e.panth.io',
                    'fqdn' => 'ece44d39-1d2f-4c12-b3f7-bc9f9241225e.panth.io',
                ],
                '34bc64f2b779461d805fcbb8675866c8' =>
                (object)[
                    'access_key' => 'c0a1a7c8327f47a1bca96aab68b54676',
                    'binding_created' => 1471385344,
                    'cached_at' => 1471547694,
                    'comment' => 'Created by environment creation.',
                    'converged_at' => 1471547694,
                    'current_commit' => '1fdf194d3d7a0c930a4f118e1398412765320328',
                    'current_ref' => 'refs/tags/pantheon_test_1',
                    'endpoint' => 'e46ff168-8fe3-4a8a-abc2-3b50e08ddc92',
                    'endpoint_zone' => 'chios',
                    'environment' => 'test',
                    'host' => '104.130.221.158',
                    'port' => 17493,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'appserver',
                    'ideal_host' => '104.130.221.158',
                    'ideal_fqdn' => 'e46ff168-8fe3-4a8a-abc2-3b50e08ddc92.panth.io',
                    'fqdn' => 'e46ff168-8fe3-4a8a-abc2-3b50e08ddc92.panth.io',
                ],
                '1ede71b414a9452380e5952ca061fbc0' =>
                (object)[
                    'binding_created' => 1471385345,
                    'client' => 'fusedav-release',
                    'cluster' => 'valhalla4',
                    'comment' => 'Created by environment creation.',
                    'environment' => 'test',
                    'host' => 'remote',
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'fileserver',
                    'fqdn' => 'valhalla4.cluster.panth.io',
                ],
                '947f61aec1c347669a8002f10ccfa6ca' =>
                (object)[
                    'binding_created' => 1471385343,
                    'client' => 'fusedav-release',
                    'cluster' => 'valhalla4',
                    'comment' => 'Created by environment creation.',
                    'converged_at' => 1471385386,
                    'create_site' => false,
                    'environment' => 'dev',
                    'host' => 'remote',
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'fileserver',
                    'fqdn' => 'valhalla4.cluster.panth.io',
                ],
                '2e6413eea72d48faab414ff9d6027452' =>
                (object)[
                    'binding_created' => 1470329762,
                    'cached_at' => 1473337382,
                    'comment' => 'Created by adjust binding count at Thu Aug 4 16:56:02 2016.',
                    'converged_at' => 1473337382,
                    'endpoint' => '6159bd13-ed38-48b3-ac41-699b2369cdbd',
                    'endpoint_zone' => 'chios',
                    'environment' => 'live',
                    'host' => '23.253.58.38',
                    'password' => 'd10342e784aa4965a33ff42dff8736c4',
                    'port' => 11314,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'cacheserver',
                    'ideal_host' => '23.253.58.38',
                    'ideal_fqdn' => '6159bd13-ed38-48b3-ac41-699b2369cdbd.panth.io',
                    'fqdn' => '6159bd13-ed38-48b3-ac41-699b2369cdbd.panth.io',
                ],
                '9f12ce4891a54a69887628170e4dbfd9' =>
                (object)[
                    'binding_created' => 1470329766,
                    'cached_at' => 1473337276,
                    'comment' => 'Created by adjust binding count at Thu Aug4 16:56:06 2016.',
                    'converged_at' => 1473337276,
                    'endpoint' => '6159bd13-ed38-48b3-ac41-699b2369cdbd',
                    'endpoint_zone' => 'chios',
                    'environment' => 'multidev',
                    'host' => '23.253.58.38',
                    'password' => 'a172e896cbda44a8bb17719b24c4c74d',
                    'port' => 11315,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'cacheserver',
                    'ideal_host' => '23.253.58.38',
                    'ideal_fqdn' => '6159bd13-ed38-48b3-ac41-699b2369cdbd.panth.io',
                    'fqdn' => '6159bd13-ed38-48b3-ac41-699b2369cdbd.panth.io',
                ],
                '929553873fd74e4ebe17010ac521b1ce' =>
                (object)[
                    'binding_created' => 1470329764,
                    'cached_at' => 1473370391,
                    'comment' => 'Created by adjust binding count at Thu Aug4 16:56:04 2016.',
                    'converged_at' => 1473370391,
                    'endpoint' => '106e9705-522d-4e24-9ea1-ee66307b052e',
                    'endpoint_zone' => 'chios',
                    'environment' => 'dev',
                    'host' => '23.253.39.24',
                    'password' => 'bf19fbfd2f584df591aa1c8666a8f126',
                    'port' => 11279,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'cacheserver',
                    'ideal_host' => '23.253.39.24',
                    'ideal_fqdn' => '106e9705-522d-4e24-9ea1-ee66307b052e.panth.io',
                    'fqdn' => '106e9705-522d-4e24-9ea1-ee66307b052e.panth.io',
                ],
                '01862c1dc3924fef971a40d30369deff' =>
                (object)[
                    'binding_created' => 1470329760,
                    'cached_at' => 1473336876,
                    'comment' => 'Created by adjust binding count at Thu Aug4 16:56:00 2016.',
                    'converged_at' => 1473336876,
                    'endpoint' => '6159bd13-ed38-48b3-ac41-699b2369cdbd',
                    'endpoint_zone' => 'chios',
                    'environment' => 'test',
                    'host' => '23.253.58.38',
                    'password' => '2bb6c2365ee64cdc9be1d840dbaf426b',
                    'port' => 11313,
                    'ready' => true,
                    'site' => '11111111-1111-1111-1111-111111111111',
                    'slave_of' => null,
                    'type' => 'cacheserver',
                    'ideal_host' => '23.253.58.38',
                    'ideal_fqdn' => '6159bd13-ed38-48b3-ac41-699b2369cdbd.panth.io',
                    'fqdn' => '6159bd13-ed38-48b3-ac41-699b2369cdbd.panth.io',
                ],
        ];

        $this->bindings = $this->createBindings();
    }

    public function testGetByType()
    {
        $bindings = $this->createBindingsWithModels();

        $out = $bindings->getByType('cacheserver');
        $this->assertEquals(4, count($out));
        foreach ($out as $binding) {
            $this->assertEquals('cacheserver', $binding->get('type'));
        }

        $out = $bindings->getByType('fileserver');
        $this->assertEquals(3, count($out));
        foreach ($out as $binding) {
            $this->assertEquals('fileserver', $binding->get('type'));
        }

        $out = $bindings->getByType('codeserver');
        $this->assertEquals(1, count($out));
        foreach ($out as $binding) {
            $this->assertEquals('codeserver', $binding->get('type'));
        }

        $out = $bindings->getByType('dbserver');
        $this->assertEquals(3, count($out));
        foreach ($out as $binding) {
            $this->assertEquals('dbserver', $binding->get('type'));
        }

        $out = $bindings->getByType('other');
        $this->assertEquals(0, count($out));
    }

    public function testGetURL()
    {
        $this->assertEquals("sites/{$this->environment->getSite()->id}/bindings", $this->bindings->getUrl());
    }

    protected function createBindings()
    {
        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->id = 'dev';
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'site id';
        $this->environment->method('getSite')->willReturn($site);

        $bindings = new Bindings(['environment' => $this->environment,]);
        $bindings->setRequest($this->request);
        $bindings->setContainer($this->container);
        return $bindings;
    }

    protected function createBindingsWithModels()
    {
        $bindings = $this->createBindings();
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                "sites/{$this->environment->getSite()->id}/bindings",
                ['options' => ['method' => 'get']]
            )
            ->willReturn(['data' => $this->bindings_data]);

        $i = 0;
        foreach ($this->bindings_data as $id => $data) {
            $this->container->expects($this->at($i++))
                ->method('get')
                ->with(
                    Binding::class,
                    [
                        $data,
                        ['id' => $id, 'collection' => $bindings]
                    ]
                )
                ->willReturn(new Binding($data, ['collection' => $bindings]));
        }
        return $bindings;
    }
}
