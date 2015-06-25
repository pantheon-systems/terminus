<?php
/**
 * Testing class for \Terminus\Utils
 *
 */
use \Terminus\Helpers\Input;
use \Terminus\SiteWorkflow;
use \Terminus\SiteFactory;


 class SiteWorkFlowTest extends PHPUnit_Framework_TestCase {

   /**
    * @vcr site_workflow_create
    */
   function testSiteWorkflowCreate() {
     $site = SiteFactory::instance('phpunittest');

     $workflow = SiteWorkflow::createWorkflow('update_site_organization_membership', $site);

     $this->assertInstanceOf('Terminus\SiteWorkflow',$workflow);
     $this->assertInstanceOf('Terminus\Site',$workflow->site);
     $this->assertNull($workflow->status());
     $workflow->start();
   }

 }
