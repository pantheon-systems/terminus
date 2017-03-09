<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\UpstreamStatus;

/**
 * Class UpstreamStatusTest
 * Testing class for Pantheon\Terminus\Models\UpstreamStatus
 * @package Pantheon\Terminus\UnitTests\Models
 */
class UpstreamStatusTest extends ModelTestCase
{
    /**
     * @var Environment
     */
    protected $environment;
    /**
     * @var UpstreamStatus
     */
    protected $model;
    /**
     * @var string
     */
    protected $request_url;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->id = 'environment id';
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'site id';
        $base_branch = "refs/heads/{$this->environment->id}";
        $this->request_url = "sites/{$site->id}/code-upstream-updates?base_branch=$base_branch";
        $this->environment->method('getSite')->willReturn($site);

        $this->environment->expects($this->once())
            ->method('getBranchName')
            ->with()
            ->willReturn($this->environment->id);

        $this->model = new UpstreamStatus(null, ['environment' => $this->environment,]);
        $this->model->setRequest($this->request);
    }

    /**
     * Tests UpstreamStatus::getStatus() when the status is current
     */
    public function testGetStatusCurrent()
    {
        $this->expectIsCurrent();
        $this->assertEquals('current', $this->model->getStatus());
    }

    /**
     * Tests UpstreamStatus::getStatus() when the status is outdated
     */
    public function testGetStatusOutdated()
    {
        $this->expectIsBehind();
        $this->assertEquals('outdated', $this->model->getStatus());
    }

    /**
     * Tests UpstreamStatus::getUpdates()
     */
    public function testGetUpdates()
    {
        $expected = 'return me';
        $this->expectRequest($expected);
        $out = $this->model->getUpdates();
        $this->assertEquals($expected, $out);
    }

    /**
     * Tests UpstreamStatus::hasUpdates() when there are no updates and the environment is test or live
     */
    public function testHasNoUpdates()
    {
        $return_data = (object)[
            $this->environment->id => (object)['is_up_to_date_with_upstream' => true,],
            'test' => (object)['is_up_to_date_with_upstream' => true,],
        ];
        $this->expectIsDevelopment(false);
        $this->expectRequest($return_data);
        $this->assertFalse($this->model->hasUpdates());
    }

    /**
     * Tests UpstreamStatus::hasUpdates() when there are updates and the environment is test or live and the named env is outdated
     */
    public function testHasUpdates()
    {
        $return_data = (object)[
            $this->environment->id => (object)['is_up_to_date_with_upstream' => false,],
            'test' => (object)['is_up_to_date_with_upstream' => true,],
        ];
        $this->expectIsDevelopment(false);
        $this->expectRequest($return_data);
        $this->assertTrue($this->model->hasUpdates());
    }

    /**
     * Tests UpstreamStatus::hasUpdates() when there are updates and the environment is test or live and the named env's parent is outdated
     */
    public function testHasUpdatesFromParent()
    {
        $return_data = (object)[
            $this->environment->id => (object)['is_up_to_date_with_upstream' => true,],
            'test' => (object)['is_up_to_date_with_upstream' => false,],
        ];
        $this->expectIsDevelopment(false);
        $this->expectRequest($return_data);
        $this->assertTrue($this->model->hasUpdates());
    }

    /**
     * Tests UpstreamStatus::hasUpdates() when there are no updates and the environment is a dev environment
     */
    public function testHasNoUpdatesDev()
    {
        $this->expectIsCurrent();
        $this->assertFalse($this->model->hasUpdates());
    }

    /**
     * Tests UpstreamStatus::hasUpdates() when there are updates and the environment is a dev environment
     */
    public function testHasUpdatesDev()
    {
        $this->expectIsBehind();
        $this->assertTrue($this->model->hasUpdates());
    }

    /**
     * Sets the test to expect a result saying that the dev environment is behind on updates
     */
    private function expectIsBehind()
    {
        $this->expectIsDevelopment();
        $this->expectRequest((object)['behind' => 1,]);
    }

    /**
     * Sets the test to expect a result saying that the dev environment is current on updates
     */
    private function expectIsCurrent()
    {
        $this->expectIsDevelopment();
        $this->expectRequest((object)['behind' => 0,]);
    }

    /**
     * Sets the test to expect a call to isDevelopment with the set return value
     *
     * @param boolean
     */
    private function expectIsDevelopment($is_dev = true)
    {
        $this->environment->expects($this->once())
            ->method('isDevelopment')
            ->with()
            ->willReturn($is_dev);
    }

    /**
     * Sets the test to expect a request with specific returned data
     *
     * @param mixed
     */
    private function expectRequest($return_data = null)
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with($this->equalTo($this->request_url))
            ->willReturn(['data' => $return_data,]);
    }
}
