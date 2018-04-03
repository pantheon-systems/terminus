<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\SiteUpstream;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class SiteUpstreamTest
 * Tests the Pantheon\Terminus\Models\SiteUpstream class
 * @package Pantheon\Terminus\UnitTests\Models
 */
class SiteUpstreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the Upstream::clearCache() function
     */
    public function testClearCache()
    {
        $data = [
            'product_id' => 'upstream id',
        ];
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($workflows);
        $workflows->expects($this->once())
            ->method('create')
            ->with('clear_code_cache')
            ->willReturn($workflow);

        $model = new SiteUpstream((object)$data);
        $model->setSite($site);

        $this->assertEquals($workflow, $model->clearCache());
    }

    /**
     * Tests the Upstream::parseAttributes() function when the data has neither an ID nor a product ID
     */
    public function testParseAttributesWithoutIDAndWithoutProductID()
    {
        $data = [
            'repository_url' => 'repository.url',
            'repository_branch' => 'repo branch',
            'type' => 'custom',
        ];
        $model = new SiteUpstream((object)$data);
        $this->assertNull($model->id);
    }

    /**
     * Tests the Upstream::parseAttributes() function when the data has no ID but has a product ID
     */
    public function testParseAttributesWithoutIDButWithProductID()
    {
        $data = [
            'product_id' => 'upstream id',
            'repository_url' => 'repository.url',
            'repository_branch' => 'repo branch',
            'type' => 'custom',
        ];
        $model = new SiteUpstream((object)$data);
        $this->assertEquals($data['product_id'], $model->id);
    }

    /**
     * Tests the Upstream::parseAttributes() function when the data has an ID and no product ID
     */
    public function testParseAttributesWithoutProductIDButWithID()
    {
        $data = [
            'id' => 'upstream id',
            'repository_url' => 'repository.url',
            'repository_branch' => 'repo branch',
            'type' => 'custom',
        ];
        $model = new SiteUpstream((object)$data);
        $this->assertEquals($data['id'], $model->id);
    }

    /**
     * Tests the Upstream::parseAttributes() function when the data has both an ID and a Product ID
     */
    public function testParseAttributesWithIDAndProductID()
    {
        $data = [
            'id' => 'upstream id',
            'product_id' => 'product id',
            'repository_url' => 'repository.url',
            'repository_branch' => 'repo branch',
            'type' => 'custom',
        ];
        $model = new SiteUpstream((object)$data);
        $this->assertEquals($data['id'], $model->id);
    }

    /**
     * Tests the Upstream::serialize() function
     */
    public function testSerialize()
    {
        $data = $expected = [
            'product_id' => 'upstream id',
            'url' => 'repository.url',
            'branch' => 'repo branch',
        ];
        $model = new SiteUpstream((object)$data);
        $this->assertEquals($expected, $model->serialize());
    }

    /**
     * Tests the Upstream::__toString() function
     */
    public function testToString()
    {
        $data = [
            'product_id' => 'upstream id',
            'url' => 'repository.url',
        ];
        $expected = $data['product_id'] . ': ' . $data['url'];
        $model = new SiteUpstream((object)$data);
        $this->assertEquals($expected, $model->__toString());
    }
}
