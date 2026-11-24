<?php

namespace Pantheon\Terminus\Tests\Unit\Commands\Backup;

use Pantheon\Terminus\Commands\Backup\CreateCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use PHPUnit\Framework\TestCase;

class CreateCommandKeepForTest extends TestCase
{
    private function validate($keepFor)
    {
        $command = new CreateCommand();
        $reflection = new \ReflectionMethod($command, 'validateKeepFor');
        $reflection->setAccessible(true);
        return $reflection->invoke($command, $keepFor);
    }

    public function testOmittedKeepForDefaultsToThirtyDays()
    {
        $this->assertSame(30, $this->validate(null));
    }

    /**
     * @dataProvider validProvider
     */
    public function testValidKeepForIsReturnedAsInt($input, int $expected)
    {
        $this->assertSame($expected, $this->validate($input));
    }

    public function validProvider(): array
    {
        return [
            'one day' => [1, 1],
            'thirty as string' => ['30', 30],
            'cap' => [180, 180],
            'cap as string' => ['180', 180],
        ];
    }

    public function testAboveCapIsRejectedWithClearMessage()
    {
        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('at most 180 days');
        $this->validate(365);
    }

    /**
     * @dataProvider invalidProvider
     */
    public function testInvalidKeepForIsRejected($input)
    {
        $this->expectException(TerminusException::class);
        $this->validate($input);
    }

    public function invalidProvider(): array
    {
        return [
            'one above cap' => [181],
            'zero' => [0],
            'negative' => [-5],
            'not a number' => ['abc'],
            'float' => ['1.5'],
            'empty' => [''],
        ];
    }
}
