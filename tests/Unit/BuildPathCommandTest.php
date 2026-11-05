<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\Commands\Site\BuildPathCommand;

/**
 * Tests build path validation exposed on the site:build-path command via
 * the shared BuildPathTrait.
 */
class BuildPathCommandTest extends TestCase
{
    /**
     * @test
     * @group site
     * @group short
     * @dataProvider validBuildPathProvider
     */
    public function testValidateBuildPathAcceptsValidPaths(string $path)
    {
        $this->assertNull(BuildPathCommand::validateBuildPath($path));
    }

    public function validBuildPathProvider(): array
    {
        return [
            'empty (reset to root)' => [''],
            'single segment' => ['apps'],
            'nested' => ['apps/web'],
            'dots and dashes' => ['packages/site-a.v2'],
        ];
    }

    /**
     * @test
     * @group site
     * @group short
     * @dataProvider invalidBuildPathProvider
     */
    public function testValidateBuildPathRejectsInvalidPaths(string $path, string $expectedFragment)
    {
        $error = BuildPathCommand::validateBuildPath($path);
        $this->assertNotNull($error, sprintf('Expected "%s" to be rejected', $path));
        $this->assertStringContainsString($expectedFragment, $error);
    }

    public function invalidBuildPathProvider(): array
    {
        return [
            'absolute path' => ['/apps/web', 'relative'],
            'parent traversal' => ['apps/../etc', "'.' or '..'"],
            'backslash' => ['apps\\win', 'forward slashes'],
            'empty segment' => ['apps//web', 'empty segments'],
            'invalid char' => ['apps/we b', 'invalid characters'],
            'too long' => [str_repeat('a', 513), '512 characters'],
        ];
    }
}
