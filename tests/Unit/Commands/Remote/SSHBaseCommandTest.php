<?php

namespace Pantheon\Terminus\Tests\Unit\Commands\Remote;

use Pantheon\Terminus\Commands\Remote\DrushCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SSHBaseCommandTest extends TestCase
{
    private function call(string $method, array $args)
    {
        $command = new DrushCommand();
        $command->setLogger(new NullLogger());
        $reflection = new \ReflectionMethod($command, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($command, $args);
    }

    /**
     * @dataProvider argumentProvider
     */
    public function testRedactedCommandLineOmitsArguments(array $args, string $expected)
    {
        $line = $this->call('getCommandLine', [$args, ['TRACE_ID' => 'a-b'], true]);

        $this->assertSame("TRACE_ID=a_b $expected", $line);
        $this->assertStringNotContainsString('CANARY', $line);
    }

    /**
     * @dataProvider argumentProvider
     */
    public function testCommandSummaryOmitsArguments(array $args, string $expected)
    {
        $summary = $this->call('getCommandSummary', [$args]);

        $this->assertStringNotContainsString('CANARY', $summary);
        $this->assertStringStartsWith('drush', $summary);
    }

    public function argumentProvider(): array
    {
        return [
            'positional' => [['sql:query', 'SELECT CANARY'], 'drush sql:query [+1 args redacted]'],
            'short option' => [['sql:cli', '-pCANARY'], 'drush sql:cli [+1 args redacted]'],
            'option value' => [['x', '--password', '-CANARY'], 'drush x [+2 args redacted]'],
            'after double dash' => [['x', '--', '-pCANARY'], 'drush x [+2 args redacted]'],
            'leading option' => [['-pCANARY', 'cr'], 'drush [+2 args redacted]'],
            'no arguments' => [[], 'drush'],
            'subcommand only' => [['cr'], 'drush cr'],
        ];
    }

    public function testRedactedCommandLineOnlyKeepsAllowlistedEnvVars()
    {
        $line = $this->call('getCommandLine', [['cr'], ['TRACE_ID' => 'a-b', 'API_KEY' => 'CANARY'], true]);

        $this->assertSame('TRACE_ID=a_b API_KEY=**HIDDEN** drush cr', $line);
    }

    public function testSetEnvOptionsOnlyKeepAllowlistedEnvVarsWhenRedacted()
    {
        $env = ['TRACE_ID' => 'abc', 'API_KEY' => 'CANARY'];

        $this->assertSame(
            '-o SetEnv="TRACE_ID=abc" -o SetEnv="API_KEY=**HIDDEN**" ',
            $this->call('getSetEnvOptions', [$env, true])
        );
        $this->assertStringContainsString('API_KEY=CANARY', $this->call('getSetEnvOptions', [$env]));
    }
}
