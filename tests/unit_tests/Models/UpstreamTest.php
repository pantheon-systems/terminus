<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Models\Upstream;

/**
 * Class UpstreamTest
 * Testing class for Pantheon\Terminus\Models\Upstream
 * @package Pantheon\Terminus\UnitTests\Models
 */
class UpstreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the Upstream::getReferences() function
     */
    public function testGetReferences()
    {
        $data = [
            'id' => 'upstream id',
            'label' => 'upstream label',
            'machine_name' => 'upstream machine name',
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
        $model = new Upstream((object)$data);
        foreach ($data as $key => $value) {
            $this->assertEquals($value, $model->get($key));
        }
        $this->assertEquals($data['product_id'], $model->id);
        $this->assertEquals($data['url'], $model->get('repository_url'));
        $this->assertEquals($data['branch'], $model->get('repository_branch'));
    }

    /**
     * Tests the Upstream::serialize() function
     */
    public function testSerialize()
    {
        $data = [
            'product_id' => 'upstream id',
            'url' => 'repository.url',
            'branch' => 'repo branch',
            'key' => 'value',
        ];
        $expected = array_merge(
            $data,
            [
                'id' => $data['product_id'],
                'repository_url' => $data['url'],
                'repository_branch' => $data['branch'],
            ]
        );
        $model = new Upstream((object)$data);
        $this->assertEquals($expected, $model->serialize());

        $data2 = [
            'id' => $data['product_id'],
            'repository_url' => $data['url'],
        ];
        $model2 = new Upstream((object)$data2);
        $this->assertEquals($data2, $model2->serialize());
    }

    /**
     * Tests the Upstream::__toString() function
     */
    public function testToString()
    {
        $data = [
            'id' => 'upstream id',
            'repository_url' => 'repository.url',
        ];
        $model = new Upstream((object)$data);
        $this->assertEquals("{$data['id']}: {$data['repository_url']}", (string)$model);
    }
}
