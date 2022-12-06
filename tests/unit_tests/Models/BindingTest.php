<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\Bindings;
use Pantheon\Terminus\Models\Binding;
use Pantheon\Terminus\Models\Site;

/**
 * Class BindingTest
 * Testing class for Pantheon\Terminus\Models\Binding
 * @package Pantheon\Terminus\UnitTests\Models
 */
class BindingTest extends ModelTestCase
{
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
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'site_id';
        $this->collection = $this->getMockBuilder(Bindings::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection->method('getSite')->willReturn($site);
    }

    /**
     * Tests Binding::getUsername() when the legacy username is present in the data
     */
    public function testGetUsernameWithLegacy()
    {
        $data = [
            'id' => 'binding_id',
            'legacy_username' => 'pantheon_classic',
            'username' => 'new_pantheon',
        ];
        $model = new Binding((object)$data, ['collection' => $this->collection,]);
        $username = $model->getUsername();
        $this->assertEquals($data['legacy_username'], $username);
    }

    /**
     * Tests Binding::getUsername() when the legacy username is absent from the data
     */
    public function testGetUsernameWithoutLegacy()
    {
        $data = [
            'id' => 'binding_id',
            'username' => 'new_pantheon',
        ];
        $model = new Binding((object)$data, ['collection' => $this->collection,]);
        $username = $model->getUsername();
        $this->assertEquals($data['username'], $username);
    }
}
