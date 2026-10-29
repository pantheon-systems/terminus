<?php

namespace Pantheon\Terminus\Tests\Unit;

use Consolidation\Config\Config;
use Pantheon\Terminus\Helpers\Utility\ProgramStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ProgramStatusTest extends TestCase
{
    public function testFormatEncodesMessage()
    {
        $this->assertSame(
            "\e]7501;state=working:app=terminus:msg=" . base64_encode('Deploy code to live') . ":progress=40\e\\",
            ProgramStatus::format('working', ['msg' => 'Deploy code to live', 'progress' => 40])
        );
    }

    public function testFormatDropsEmptyMessage()
    {
        $this->assertSame("\e]7501;state=done:app=terminus\e\\", ProgramStatus::format('done', ['msg' => '']));
    }

    public function testSanitizeRemovesControlCharacters()
    {
        $this->assertSame('line one line two', ProgramStatus::sanitize("line one\n\tline two\e"));
    }

    public function testSanitizeTruncatesToLimit()
    {
        $text = ProgramStatus::sanitize(str_repeat('é', 2000));
        $this->assertLessThanOrEqual(ProgramStatus::MAX_MESSAGE_BYTES, strlen($text));
        $this->assertTrue(mb_check_encoding($text, 'UTF-8'));
    }

    public function testWritesOnlyToDecoratedOutput()
    {
        $plain = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, false);
        (new ProgramStatus($plain))->working('Syncing');
        $this->assertSame('', $plain->fetch());

        $terminal = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        (new ProgramStatus($terminal))->working('Syncing', 150);
        $this->assertSame(
            "\e]7501;state=working:app=terminus:msg=" . base64_encode('Syncing') . ":progress=100\e\\",
            $terminal->fetch()
        );
    }

    public function testDisabledWritesNothing()
    {
        $terminal = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        (new ProgramStatus($terminal, false))->done('Finished');
        $this->assertSame('', $terminal->fetch());
    }

    public function testNullOutputWritesNothing()
    {
        (new ProgramStatus(null))->error('Failed');
        $this->addToAssertionCount(1);
    }

    public function testIsEnabled()
    {
        $this->assertTrue(ProgramStatus::isEnabled(null));
        $this->assertTrue(ProgramStatus::isEnabled(new Config([])));
        $this->assertTrue(ProgramStatus::isEnabled(new Config(['program_status' => '1'])));
        $this->assertFalse(ProgramStatus::isEnabled(new Config(['program_status' => '0'])));
        $this->assertFalse(ProgramStatus::isEnabled(new Config(['program_status' => 'false'])));
    }
}
