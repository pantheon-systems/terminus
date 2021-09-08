<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class SolrCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class SolrCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Solr\EnableCommand
     *
     * @group solr
     * @group long
     */
    public function testSolrEnable()
    {
        $this->terminus("solr:enable {$this->getSiteName()}");
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Solr\DisableCommand
     *
     * @group solr
     * @group long
     */
    public function testSolrDisable()
    {
        $this->terminus("solr:disable {$this->getSiteName()}");
    }
}
