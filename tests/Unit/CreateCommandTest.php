<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\Commands\Site\CreateCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use ReflectionClass;

/**
 * Test repository name validation in CreateCommand.
 */
class CreateCommandTest extends TestCase
{
    /**
     * @dataProvider invalidRepositoryNameProvider
     */
    public function testInvalidRepositoryNames(string $repoName, string $expectedMessage): void
    {
        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->invokeValidateMethod($repoName);
    }

    /**
     * @dataProvider validRepositoryNameProvider
     */
    public function testValidRepositoryNames(string $repoName): void
    {
        $this->invokeValidateMethod($repoName);
        $this->assertTrue(true);
    }

    /**
     * Data provider for invalid repository names.
     *
     * @return array<string, array<int, string>>
     */
    public function invalidRepositoryNameProvider(): array
    {
        return [
            'empty name' => ['', 'Repository name cannot be empty'],
            'too long (101 chars)' => [str_repeat('a', 101), 'is too long. Maximum length is 100 characters'],
            'with underscore' => ['my_repo_name', 'contains invalid characters'],
            'with special characters' => ['my-repo!name', 'contains invalid characters'],
            'with spaces' => ['my repo name', 'contains invalid characters'],
            'only dashes' => ['---', 'must contain at least one alphanumeric character'],
            'starts with dash' => ['-my-repo', 'cannot begin with a dash'],
            'ends with dash' => ['my-repo-', 'cannot end with a dash'],
        ];
    }

    /**
     * Data provider for valid repository names.
     *
     * @return array<string, array<int, string>>
     */
    public function validRepositoryNameProvider(): array
    {
        return [
            'alphanumeric' => ['myrepo123'],
            'with dashes' => ['my-repo-name'],
            'mixed case' => ['MyRepoName'],
            'starts with number' => ['123-repo'],
            'ends with number' => ['repo-123'],
            'exactly 100 chars' => [str_repeat('a', 100)],
        ];
    }

    /**
     * Invoke the protected validateRepositoryName method using reflection.
     *
     * @param string $repoName Repository name to validate
     * @throws TerminusException if validation fails
     */
    private function invokeValidateMethod(string $repoName): void
    {
        $command = new CreateCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('validateRepositoryName');
        $method->setAccessible(true);
        $method->invoke($command, $repoName);
    }
}
