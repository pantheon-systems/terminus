<?php

namespace Pantheon\Terminus\Tests\Unit;

use Pantheon\Terminus\Helpers\OutputSanitizer;
use PHPUnit\Framework\TestCase;

class OutputSanitizerTest extends TestCase
{
    public function testRedactsUserinfo()
    {
        $this->assertSame(
            'https://***@github.com/a/b.git',
            OutputSanitizer::sanitize('https://user:pa55@github.com/a/b.git')
        );
        $this->assertSame('https://github.com/a/b', OutputSanitizer::sanitize('https://github.com/a/b'));
        $this->assertSame('https://github.com/a@b', OutputSanitizer::sanitize('https://github.com/a@b'));
    }

    public function testStripsControlCharacters()
    {
        $this->assertSame('ab[31mc]8;;x', OutputSanitizer::sanitize("a\x1bb\x1b[31mc\u{009d}\x07]8;;x"));
        $this->assertSame("a\u{00e9}", OutputSanitizer::sanitize("a\u{00e9}"));
    }

    public function testEscapesFormatterTags()
    {
        $this->assertSame('\<fg=red\>x', OutputSanitizer::sanitize('<fg=red>x'));
    }

    public function testNonStringsPassThrough()
    {
        $this->assertSame(5, OutputSanitizer::sanitize(5));
        $this->assertNull(OutputSanitizer::sanitize(null));
    }
}
