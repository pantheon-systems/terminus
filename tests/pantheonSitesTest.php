<?php
/**
 * @file
 * PHPUnit Tests for pantheon-sites using Drush's test framework.
 */

class pantheonSitesTest extends Drush_UnitTestCase {

  public function __construct() {
    parent::__construct();
    // Load Terminus.
    require_once __DIR__ . '/../terminus.drush.inc';
  }

  public function testPantheonSites() {
    // Null.
    $this->assertFalse(terminus_validate_sites(NULL));

    // Empty string.
    $this->assertFalse(terminus_validate_sites(''));

    // Not JSON.
    $this->assertFalse(terminus_validate_sites('fail'));

    // Valid JSON.
    $this->assertFalse(terminus_validate_sites('{}'));

    // Invalid site UUID.
    $invalid_site_uuid = <<<JSON
{
    "fail": {
        "information": {
            "service_level": "basic",
            "name": "launchdemo",
            "created": 1380056038,
            "created_at": "2013-09-24T20:53:58",
            "instrument": "12345678-90ab-cdef-1234-567890abcdef",
            "upstream": {
                "url": "git://github.com/pantheon-systems/drops-7.git",
                "branch": "master"
            },
            "owner": "12345678-90ab-cdef-1234-567890abcdef",
            "organization": "12345678-90ab-cdef-1234-567890abcdef",
            "preferred_zone": "chios"
        },
        "metadata": null
    }
}
JSON;
    $this->assertFalse(terminus_validate_sites($invalid_site_uuid));

    // No information.
    $no_info = <<<JSON
{
    "12345678-90ab-cdef-1234-567890abcdef": {
        "metadata": null
    }
}
JSON;
    $this->assertFalse(terminus_validate_sites($no_info));

    // No name.
    $no_name = <<<JSON
{
    "12345678-90ab-cdef-1234-567890abcdef": {
        "information": {
            "service_level": "basic",
            "created": 1380056038,
            "created_at": "2013-09-24T20:53:58",
            "instrument": "12345678-90ab-cdef-1234-567890abcdef",
            "upstream": {
                "url": "git://github.com/pantheon-systems/drops-7.git",
                "branch": "master"
            },
            "owner": "12345678-90ab-cdef-1234-567890abcdef",
            "organization": "12345678-90ab-cdef-1234-567890abcdef",
            "preferred_zone": "chios"
        },
        "metadata": null
    }
}
JSON;
    $this->assertFalse(terminus_validate_sites($no_name));

    // Valid sites.
    $valid_sites = <<<JSON
{
    "12345678-90ab-cdef-1234-567890abcdef": {
        "information": {
            "service_level": "basic",
            "name": "launchdemo",
            "created": 1380056038,
            "created_at": "2013-09-24T20:53:58",
            "instrument": "12345678-90ab-cdef-1234-567890abcdef",
            "upstream": {
                "url": "git://github.com/pantheon-systems/drops-7.git",
                "branch": "master"
            },
            "owner": "12345678-90ab-cdef-1234-567890abcdef",
            "organization": "12345678-90ab-cdef-1234-567890abcdef",
            "preferred_zone": "chios"
        },
        "metadata": null
    }
}
JSON;
    $this->assertTRUE(terminus_validate_sites($valid_sites));å
  }
}