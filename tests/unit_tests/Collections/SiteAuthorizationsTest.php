<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\SiteAuthorizations;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\SiteAuthorization;

/**
 * Class SiteAuthorizationsTest
 * Testing class for Pantheon\Terminus\Collections\SiteAuthorizations
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class SiteAuthorizationsTest extends CollectionTestCase
{
    /**
     * @var array
     */
    protected $data;
    /**
     * @var Site
     */
    protected $site;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->data = [
            'authorized' => (object)[
                'id' => 'authorized',
                'is_user_authorized' => true,
            ],
            'unauthorized' => (object)[
                'id' => 'unauthorized',
                'is_user_authorized' => false,
            ],
        ];
        $this->collection = new SiteAuthorizations(['data' => $this->data, 'site' => $this->site,]);

        $i = 0;
        foreach ($this->data as $id => $item) {
            $params = [$item, ['id' => $id, 'collection' => $this->collection,],];
            $this->container->expects($this->at($i++))
                ->method('get')
                ->with(SiteAuthorization::class, $params)
                ->willReturn(new SiteAuthorization($params[0], $params[1]));
        }

        $this->collection->setContainer($this->container);
        $this->collection->fetch();
    }

    /**
     * Tests the SiteAuthorizations::can(string) function
     */
    public function testCan()
    {
        $this->assertTrue($this->collection->can('authorized'));
        $this->assertFalse($this->collection->can('unauthorized'));
    }
}
