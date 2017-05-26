<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Upstream;

class UpstreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the Upstream::getReferences() function
     */
    public function testGetReferences()
    {
        $data = [
            'id' => 'upstream id',
            'longname' => 'upstream label',
            'machinename' => 'upstream machine name',
        ];

        $model = new Upstream((object)$data);
        $this->assertEquals(array_values($data), $model->getReferences());
    }

    /**
     * Tests the Upstream::parseAttributes() function
     */
    public function testParseAttributes()
    {
        $data = [
            'product_id' => 'upstream id',
            'url' => 'repository.url',
            'branch' => 'repo branch',
            'key' => 'value',
        ];
        $model = new Upstream((object)['attributes' => (object)$data,]);
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $model->get($key));
        }
        $this->assertEquals($data['product_id'], $model->id);
    }

    /**
     * Tests the Upstream::serialize() function
     */
    public function testSerialize()
    {
        $expected = $data = [
            'product_id' => 'upstream id',
            'url' => 'repository.url',
            'branch' => 'repo branch',
            'key' => 'value',
        ];
        $expected['id'] = $data['product_id'];
        $model = new Upstream((object)$data);
        $this->assertEquals($expected, $model->serialize());

        unset($expected['id']);
        unset($expected['key']);
        $model2 = new Upstream((object)$data);
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'site id';
        $model2->site = $site;
        $this->assertEquals($expected, $model2->serialize());
    }

    /**
     * Tests the Upstream::__toString() function
     */
    public function testToString()
    {
        $data = [
            'id' => 'upstream id',
            'url' => 'repository.url',
        ];
        $model = new Upstream((object)$data);
        $this->assertEquals("{$data['id']}: {$data['url']}", (string)$model);
    }
}
