<?php

use Terminus\Models\Site;
use Terminus\Models\Upstream;

/**
 * Testing class for Terminus\Models\Upstream
 */
class UpstreamTest extends PHPUnit_Framework_TestCase {

    /**
     * @var Site
     */
  private $site;

  /**
     * @inheritdoc
     *
     * @vcr site_upstream-info
     */
  public function setUp() {
    parent::setUp();
    logInWithBehatCredentials();
    $this->site = new Site((object)['id' => '11111111-1111-1111-1111-111111111111',]);
  }

    /**
     * Exercises the constructor to ensure proper setup
     *
     * @return void
     *
     * @vcr site_upstream-info
     */
  public function testConstructor() {
    $empty_object = (object)[];

    //Constructing with just an empty object
    $upstream = new Upstream($empty_object);
    $this->assertNotInstanceOf('Terminus\Models\Site', $upstream->site);
    $this->assertNull($upstream->id);

    //Constructing with a site given as an option.
    $upstream_with_site = new Upstream($empty_object, ['site' => $this->site,]);
    $this->assertNull($upstream_with_site->id);
    $this->assertEquals($upstream_with_site->site, $this->site);

    //Getting the Upstream property instantiated by a Site
    $this->site->fetch();
    $upstream_from_site = $this->site->upstream;
    $this->assertNotNull($upstream_from_site->id);
    $this->assertEquals($upstream_from_site->site, $this->site);
  }

    /**
     * Exercises the fetch function to ensure that data is appropriately extracted from the API
     *
     * @return void
     *
     * @vcr site_upstream-info
     */
  public function testFetch() {
    // Checking that fetch fills in attributes
    $empty_object = (object)[];
    $upstream_with_site = new Upstream($empty_object, ['site' => $this->site,]);
    $this->assertNull($upstream_with_site->get('product_id'));
    $this->assertNull($upstream_with_site->get('url'));
    $this->assertNull($upstream_with_site->get('branch'));
    $upstream_with_site->fetch();
    $this->assertNotNull($upstream_with_site->get('product_id'));
    $this->assertNotNull($upstream_with_site->get('url'));
    $this->assertNotNull($upstream_with_site->get('branch'));
  }

    /**
     * Exercises the getStatus function to ensure that when there are no updates, the status
     * is 'current'
     *
     * @return void
     *
     * @vcr site_upstream-info_up-to-date
     */
  public function testGetStatusCurrent() {
    $this->assertEquals($this->site->upstream->getStatus(), 'current');
  }

    /**
     * Exercises the getStatus function to ensure that when there are updates, the status
     * is 'outdated'
     *
     * @return void
     *
     * @vcr site_upstream-info
     */
  public function testGetStatusOutdated () {
    $this->assertEquals($this->site->upstream->getStatus(), 'outdated');
  }

    /**
     * Exercises the hasUpdates function to ensure that when there are no updates, this
     * returns false
     *
     * @return void
     *
     * @vcr site_upstream-info_up-to-date
     */
  public function testHasUpdatesCurrent() {
    $this->assertFalse($this->site->upstream->hasUpdates());
  }

    /**
     * Exercises the getStatus function to ensure that when there are updates, this returns true
     *
     * @return void
     *
     * @vcr site_upstream-info
     */
  public function testHasUpdatesOutdated() {
    $this->assertTrue($this->site->upstream->hasUpdates());
  }

    /**
     * Exercises the getUpdates function to ensure that data is appropriately extracted from the API
     *
     * @return void
     *
     * @vcr site_upstream-info
     */
  public function testGetUpdates() {
    $empty_object = (object)[];
    $upstream_with_site = new Upstream($empty_object, ['site' => $this->site,]);
    $updates = $upstream_with_site->getUpdates();
    $this->assertObjectHasAttribute('remote_head', $updates);
    $this->assertObjectHasAttribute('ahead', $updates);
    $this->assertObjectHasAttribute('behind', $updates);
  }

    /**
     * Exercises the serialize function to ensure that data is appropriately formatted for use
     *
     * @return void
     *
     * @vcr site_upstream-info
     */
  public function testSerialize() {
    $empty_object = (object)[];
    $upstream_with_site = new Upstream($empty_object, ['site' => $this->site,]);
    $data = $upstream_with_site->fetch()->serialize();
    $this->assertArrayHasKey('url', $data);
    $this->assertNotNull($data['url']);
    $this->assertArrayHasKey('product_id', $data);
    $this->assertNotNull($data['product_id']);
    $this->assertArrayHasKey('branch', $data);
    $this->assertNotNull($data['branch']);
    $this->assertArrayHasKey('status', $data);
    $this->assertEquals($data['status'], $upstream_with_site->getStatus());
  }

}