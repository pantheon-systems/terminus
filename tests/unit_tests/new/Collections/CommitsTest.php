<?php


namespace Pantheon\Terminus\UnitTests\Collections;


use Pantheon\Terminus\Collections\Commits;
use Pantheon\Terminus\Models\Environment;

class CommitsTest extends CollectionTestCase
{

    public function testGetURL() {
        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->site = (object)['id' => 'abc'];
        $this->environment->id = 'dev';

        $commits = new Commits(['environment' => $this->environment]);

        $this->assertEquals('sites/abc/environments/dev/code-log', $commits->getUrl());
    }
}
