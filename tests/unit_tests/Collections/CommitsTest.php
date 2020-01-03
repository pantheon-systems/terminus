<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\Commits;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;

/**
 * Class CommitsTest
 * Testing class for Pantheon\Terminus\Collections\Commits
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class CommitsTest extends CollectionTestCase
{
    public function testGetURL()
    {
        $this->environment = $this->createMock(Environment::class);
        $this->environment->id = 'dev';
        $site = $this->createMock(Site::class);
        $site->id = 'site id';
        $this->environment->method('getSite')->willReturn($site);

        $commits = new Commits(['environment' => $this->environment,]);

        $this->assertEquals("sites/{$site->id}/environments/{$this->environment->id}/code-log", $commits->getUrl());
    }
}
