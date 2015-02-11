<?php
/**
 * Testing class for \Terminus\Utils
 *
 */
use \Terminus\Helpers\Input;
use \Terminus\SiteWorkflow;
use \Terminus\SiteFactory;
use \VCR\VCR;

/**
 * @vcr workflowtest
 */
 class SiteWorkFlowTest extends PHPUnit_Framework_TestCase {

   function testSiteWorkflowCreate() {
     $site = SiteFactory::instance('phpunittest');
     $workflow = SiteWorkflow::createWorkflow('update_site_status', $site);
     $this->assertInstanceOf('Terminus\SiteWorkflow',$workflow);
     $this->assertInstanceOf('Terminus\Site',$workflow->site);
     $this->assertNull($workflow->status());
     $workflow->start('GET');
     $workflow->wait();
     $this->assertEquals($workflow->status('result'),'succeeded');
   }

 }
