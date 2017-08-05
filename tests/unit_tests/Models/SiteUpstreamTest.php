<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Models\SiteUpstream;

/**
 * Class SiteUpstreamTest
 * Tests the Pantheon\Terminus\Models\SiteUpstream class
 * @package Pantheon\Terminus\UnitTests\Models
 */
class SiteUpstreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the Upstream::parseAttributes() function
     */
    public function testParseAttributes()
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
