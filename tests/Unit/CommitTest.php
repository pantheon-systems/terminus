<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\Models\Commit;

class CommitTest extends TestCase
{
    public function testSerializeWithNullLabels()
    {
        $commit = new Commit((object) [
            'datetime' => '2026-08-19T14:26:01Z',
            'author' => 'Kevin Porras',
            'labels' => null,
            'hash' => '85df13055962be862ef5ffc691cfedb76af63a8c',
            'message' => 'Update README.md',
        ]);

        $serialized = $commit->serialize();

        $this->assertSame('', $serialized['labels']);
    }

    public function testSerializeWithLabels()
    {
        $commit = new Commit((object) [
            'datetime' => '2026-08-19T14:26:01Z',
            'author' => 'Kevin Porras',
            'labels' => ['dev', 'test'],
            'hash' => '85df13055962be862ef5ffc691cfedb76af63a8c',
            'message' => 'Update README.md',
        ]);

        $serialized = $commit->serialize();

        $this->assertSame('dev, test', $serialized['labels']);
    }
}
