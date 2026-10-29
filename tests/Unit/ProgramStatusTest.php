<?php

namespace Pantheon\Terminus\Tests\Unit;

use Consolidation\Config\Config;
use Pantheon\Terminus\Helpers\Utility\ProgramStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\StreamOutput;

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

    public function testBlockedIsAQuestion()
    {
        $terminal = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        (new ProgramStatus($terminal))->blocked('Are you sure?');
        $this->assertSame(
            "\e]7501;state=blocked:app=terminus:kind=question:msg=" . base64_encode('Are you sure?') . "\e\\",
            $terminal->fetch()
        );
    }

    public function testStartedReportsCommandName()
    {
        $terminal = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $status = new ProgramStatus($terminal);
        $status->started(new ArgvInput(['terminus', '--yes', 'env:clear-cache', 'site.dev']));
        $this->assertSame(
            "\e]7501;state=working:app=terminus:msg=" . base64_encode('env:clear-cache') . "\e\\",
            $terminal->fetch()
        );

        $status->started(new ArgvInput(['terminus']));
        $this->assertSame('', $terminal->fetch());
    }

    public function testWhileAskingReportsBlockedThenWorking()
    {
        ProgramStatus::setEnabled(true);
        $terminal = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $input = new ArgvInput(['terminus', 'site:create']);
        (new ProgramStatus($terminal))->started($input);
        $terminal->fetch();

        $answer = ProgramStatus::whileAsking($input, $terminal, '<question>Pick an org</question>', fn () => 'org');
        $this->assertSame('org', $answer);
        $this->assertSame(
            "\e]7501;state=blocked:app=terminus:kind=question:msg=" . base64_encode('Pick an org') . "\e\\"
            . "\e]7501;state=working:app=terminus:msg=" . base64_encode('site:create') . "\e\\",
            $terminal->fetch()
        );

        $input->setInteractive(false);
        ProgramStatus::whileAsking($input, $terminal, 'Pick an org', fn () => 'org');
        $this->assertSame('', $terminal->fetch());

        $input->setInteractive(true);
        ProgramStatus::setEnabled(false);
        ProgramStatus::whileAsking($input, $terminal, 'Pick an org', fn () => 'org');
        $this->assertSame('', $terminal->fetch());
    }

    public function testFinishedClearsAfterSuccess()
    {
        $terminal = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $status = new ProgramStatus($terminal);
        $status->started(new ArgvInput(['terminus', 'site:list']));
        $terminal->fetch();
        $status->finished(0);
        $this->assertSame("\e]7501;state=clear:app=terminus\e\\", $terminal->fetch());
    }

    public function testFinishedReportsErrorAfterFailure()
    {
        $terminal = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $status = new ProgramStatus($terminal);
        $status->started(new ArgvInput(['terminus', 'env:clear-cache']));
        $terminal->fetch();
        $status->finished(1);
        $this->assertSame(
            "\e]7501;state=error:app=terminus:msg=" . base64_encode('env:clear-cache failed.') . "\e\\",
            $terminal->fetch()
        );
    }

    public function testFinishedKeepsWorkflowResult()
    {
        $terminal = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $status = new ProgramStatus($terminal);
        $status->started(new ArgvInput(['terminus', 'env:clear-cache']));
        $status->done('Clear cache for dev finished.');
        $terminal->fetch();
        $status->finished(0);
        $this->assertSame('', $terminal->fetch());
    }

    public function testForcedDecorationOnRedirectWritesNothing()
    {
        $stream = fopen('php://memory', 'w+');
        $redirected = new StreamOutput($stream, StreamOutput::VERBOSITY_NORMAL, true);
        (new ProgramStatus($redirected))->working('Syncing');
        rewind($stream);
        $this->assertSame('', stream_get_contents($stream));
    }

    public function testQuietWritesNothing()
    {
        $quiet = new BufferedOutput(BufferedOutput::VERBOSITY_QUIET, true);
        (new ProgramStatus($quiet))->working('Syncing');
        $this->assertSame('', $quiet->fetch());
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
        $this->assertFalse(ProgramStatus::isEnabled(null));
        $this->assertFalse(ProgramStatus::isEnabled(new Config([])));
        $this->assertTrue(ProgramStatus::isEnabled(new Config(['program_status' => 'true'])));
        $this->assertTrue(ProgramStatus::isEnabled(new Config(['program_status' => '1'])));
        $this->assertFalse(ProgramStatus::isEnabled(new Config(['program_status' => '0'])));
        $this->assertFalse(ProgramStatus::isEnabled(new Config(['program_status' => 'false'])));
    }
}
