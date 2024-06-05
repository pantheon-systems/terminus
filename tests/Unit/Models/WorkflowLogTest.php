<?php

namespace Pantheon\Terminus\Tests\Unit\Models;

use Pantheon\Terminus\Models\WorkflowLog;

/**
 *
 */
class WorkflowLogTest extends ModelTestBase
{
    /**
     * @param array $data
     * @test
     * @dataProvider dataProvider
     */
    public function testModel(array $data): void
    {
        $model = $this->getClass();
        $model = new $model((object)$data);
        $this->assertEquals($data['workflow']['id'], $model->workflow->id);
        $this->assertEquals(round($data['workflow']['started_at']), $model->workflow->started_at->getTimestamp());
        $this->assertEquals(round($data['workflow']['finished_at']), $model->workflow->finished_at->getTimestamp() ?? 0);
        $this->assertEquals($data['actor']['id'], $model->actor->id);
        $this->assertEquals($data['kind'], $model->kind);
    }

    /**
     * @return array
     */
    public function dataProvider(): array
    {
        return [ json_decode(
            file_get_contents(
                dirname(TERMINUS_BIN_FILE) . '/tests/fixtures/WorkflowLogsCollectionTest.json'
            ),
            true
        ) ];
    }

    /**
     * @return string
     */
    protected function getClass(): string
    {
        return WorkflowLog::class;
    }
}
