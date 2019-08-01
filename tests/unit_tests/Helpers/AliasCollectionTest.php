<?php

namespace Pantheon\Terminus\UnitTests\Helpers;

use Pantheon\Terminus\Helpers\AliasEmitters\AliasCollection;
use PHPUnit\Framework\TestCase;

class AliasCollectionTest extends TestCase
{
    /**
     * aliasCollectionSortingValues provides the expected results and inputs
     * for testAliasCollectionSorting.
     *
     * @return array
     */
    public function aliasCollectionSortingValues()
    {
        return [
            [
                'agency,demo,personalsite',
                AliasFixtures::aliasCollection(
                    [
                        'personalsite' => [],
                        'demo' => [],
                        'agency' => [],
                    ]
                ),
            ],
            [
                'site9,site13,site78,site201',
                AliasFixtures::aliasCollection(
                    [
                        'site201' => [],
                        'site13' => [],
                        'site9' => [],
                        'site78' => [],
                    ]
                ),
            ],
        ];
    }

    /**
     * testAliasCollectionSorting confirms that the alias collection sorts
     * its inputs correctly
     *
     * @param string $expected
     * @param AliasCollection $aliasCollection
     *
     * @dataProvider aliasCollectionSortingValues
     */
    public function testAliasCollectionSorting($expected, $aliasCollection)
    {
        $all = $aliasCollection->all();
        $actual = implode(',', array_keys($all));
        $this->assertEquals($expected, $actual);
    }
}
