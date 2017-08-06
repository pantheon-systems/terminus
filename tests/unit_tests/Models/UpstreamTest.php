<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Models\Upstream;

/**
 * Class UpstreamTest
 * Tests the Pantheon\Terminus\Models\Upstream class
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
     * Tests the Upstream::serialize() function
     */
    public function testSerialize()
    {
        $data = $expected = [
            'repository_url' => 'repository.url',
            'label' => 'upstream label',
            'machine_name' => 'upstream machine name',
            'id' => 'id',
            'key' => 'value',
        ];
        $expected['organization'] = null;
        $model = new Upstream((object)$data);
        $this->assertEquals($expected, $model->serialize());
    }
}
