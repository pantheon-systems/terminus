<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class SolrCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SolrCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Solr\EnableCommand
     * @covers \Pantheon\Terminus\Commands\Solr\DisableCommand
     *
     * @group solr
     * @gropu short
     */
    public function testSolrEnableDisable()
    {
        $sitename = getenv('TERMINUS_SITE');
        $this->terminus("solr:enable {$sitename}");
        $this->terminus("solr:disable {$sitename}");
    }
}
