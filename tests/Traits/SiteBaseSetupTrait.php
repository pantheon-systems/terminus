<?php

namespace Pantheon\Terminus\Tests\Traits;

trait SiteBaseSetupTrait
{

    protected $testSitename;
    protected $org = "5ae1fa30-8cc4-4894-8ca9-d50628dcba17";

    public function getRandomSiteFromCIFixtures(): string
    {
        if (!isset($this->testSitename)) {
            $response = $this->terminus("org:site:list 5ae1fa30-8cc4-4894-8ca9-d50628dcba17 --format=json");
            $siteList = json_decode(
                $response,
                true,
                JSON_THROW_ON_ERROR
            );
            $this->assertIsArray(
                $siteList,
                "Response from newly-created site should be unserialized json"
            );
            shuffle($siteList);
            $this->testSitename = reset($siteList)['name'];
        }
        return $this->testSitename;
    }
}
