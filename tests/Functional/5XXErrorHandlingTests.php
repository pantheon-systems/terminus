<?php

namespace Pantheon\Terminus\Tests\Functional;

use GuzzleHttp\Client;
use Pantheon\Terminus\Config\DefaultsConfig;
use Pantheon\Terminus\Tests\Config\MockHandlers;

/**
 * Class 5XXErrorHandlingTests
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class FiveXXErrorHandlingTests extends TerminusTestBase 
{
    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->isSiteFrameworkDrupal()) {
            $this->markTestSkipped(
                'A Drupal-based test site is required to test Drush-related "drush:aliases" command.'
            );
        }
    }

		public function test502Response()
	{
		$client = MockHandlers::getClient();
		$response = $client->request('GET', '/');
		$this->assertEquals(502, $response->getStatusCode());
		$this->assertStringContainsString('502 Bad Gateway', $response->getBody());
	}
}