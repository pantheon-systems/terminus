<?php

namespace Pantheon\Terminus\Tests\Unit\Collections;

use Composer\Autoload\ClassLoader;
use Pantheon\Terminus\Collections\WorkflowLogsCollection;
use Pantheon\Terminus\Terminus;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class WorkflowLogsCollectionTest extends CollectionTestBase
{
    protected \ReflectionClass $reflector;


    /**
     * @param array $data
     * @return mixed
     * @test
     * @throws \ReflectionException
     * @dataProvider dataProvider
     */
    public function testCollection(array $data)
    {

        $collection = $this->reflector->newInstanceWithoutConstructor();
        $collection->setData($data);
        $this->assertCount($collection->count(), $data, 'The collection should have the same number of items as the data array.');
    }

    /**
     * @return mixed
     */
    public function dataProvider()
    {

        return [
            [ json_decode(
                file_get_contents(
                    dirname(TERMINUS_BIN_FILE) . '/tests/fixtures/WorkflowLogsCollectionTest.json'
                )
            ) ]
        ];
    }

    /**
     * @return string
     */
    protected function getClass(): string
    {
        return WorkflowLogsCollection::class;
    }
}
