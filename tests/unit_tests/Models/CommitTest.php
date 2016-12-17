<?php


namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Models\Commit;

class CommitTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $data = [
            'datetime' => '2016-09-21T12:21:18',
            'author' => 'Daisy Duck',
            'labels' => ['test', 'dev'],
            'hash' => 'c65e638f03cabc7b97e686bb9de843b7173e329a',
            'message' => str_pad(" Add some new code\nAnother Line Here\tTab", 100, '-'),
        ];
        $commit = new Commit((object)$data);
        $actual = $commit->serialize();
        $expected = [
            'datetime' => '2016-09-21T12:21:18',
            'author' => 'Daisy Duck',
            'labels' => 'test, dev',
            'hash' => 'c65e638f03cabc7b97e686bb9de843b7173e329a',
            'message' => str_pad('Add some new code Another Line Here Tab', 50, '-'),
        ];
        $this->assertEquals($expected, $actual);
    }
}
