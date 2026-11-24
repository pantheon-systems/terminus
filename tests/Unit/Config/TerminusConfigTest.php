<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Tests\Unit\Config;

use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Tests\Unit\UnitTestCase;

/**
 * @covers \Pantheon\Terminus\Config\TerminusConfig
 */
class TerminusConfigTest extends UnitTestCase
{
    private TerminusConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new TerminusConfig();
    }

    /**
     * @test
     */
    public function combineAddsMultipleValues(): void
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $result = $this->config->combine($data);

        $this->assertSame($this->config, $result);
        $this->assertEquals('value1', $this->config->get('key1'));
        $this->assertEquals('value2', $this->config->get('key2'));
        $this->assertEquals('value3', $this->config->get('key3'));
    }

    /**
     * @test
     */
    public function getConstantFromKeyConvertsToUppercase(): void
    {
        $result = $this->config->getConstantFromKey('cache_dir');

        $this->assertEquals('TERMINUS_CACHE_DIR', $result);
    }

    /**
     * @test
     */
    public function getConstantFromKeyHandlesAlreadyUppercase(): void
    {
        $result = $this->config->getConstantFromKey('HOST');

        $this->assertEquals('TERMINUS_HOST', $result);
    }

    /**
     * @test
     */
    public function setConvertsConstantNameToKey(): void
    {
        $this->config->set('TERMINUS_HOST', 'example.com');

        $this->assertEquals('example.com', $this->config->get('host'));
    }

    /**
     * @test
     */
    public function setWorksWithRegularKey(): void
    {
        $this->config->set('custom_key', 'custom_value');

        $this->assertEquals('custom_value', $this->config->get('custom_key'));
    }

    /**
     * @test
     */
    public function keysReturnsAllConfigKeys(): void
    {
        $this->config->set('key1', 'value1');
        $this->config->set('key2', 'value2');

        $keys = $this->config->keys();

        $this->assertContains('key1', $keys);
        $this->assertContains('key2', $keys);
    }

    /**
     * @test
     */
    public function getSourceReturnsUnknownByDefault(): void
    {
        $this->config->set('some_key', 'some_value');

        $source = $this->config->getSource('some_key');

        $this->assertEquals('Unknown', $source);
    }

    /**
     * @test
     */
    public function getSourceNameReturnsUnknownByDefault(): void
    {
        $this->assertEquals('Unknown', $this->config->getSourceName());
    }

    /**
     * @test
     */
    public function fixDirectorySeparatorsNormalizesSlashes(): void
    {
        $path = 'path/to\\mixed/slashes';

        $result = $this->config->fixDirectorySeparators($path);

        // Result should only contain the system's directory separator
        if (DIRECTORY_SEPARATOR === '/') {
            $this->assertStringNotContainsString('\\', $result);
        } else {
            $this->assertStringNotContainsString('/', $result);
        }
        $this->assertStringContainsString(DIRECTORY_SEPARATOR, $result);
    }

    /**
     * @test
     */
    public function fixDirectorySeparatorsHandlesNull(): void
    {
        $result = $this->config->fixDirectorySeparators(null);

        $this->assertEquals('', $result);
    }

    /**
     * @test
     */
    public function formatDatetimeFormatsUnixTimestamp(): void
    {
        $this->config->set('date_format', 'Y-m-d');
        $timestamp = strtotime('2024-01-15 12:00:00');

        $result = $this->config->formatDatetime((string)$timestamp);

        $this->assertEquals('2024-01-15', $result);
    }

    /**
     * @test
     */
    public function serializeReturnsExpectedKeys(): void
    {
        $this->config->set('php', '/usr/bin/php');
        $this->config->set('php_version', '8.4.0');
        $this->config->set('version', '4.1.0');

        $serialized = $this->config->serialize();

        $this->assertArrayHasKey('php_binary_path', $serialized);
        $this->assertArrayHasKey('php_version', $serialized);
        $this->assertArrayHasKey('terminus_version', $serialized);
        $this->assertArrayHasKey('terminus_path', $serialized);
    }

    /**
     * @test
     */
    public function ensureDirExistsReturnsTrueForExistingDir(): void
    {
        $result = $this->config->ensureDirExists('TERMINUS_CACHE_DIR', sys_get_temp_dir());

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function ensureDirExistsReturnsNullForNonTerminusDirKey(): void
    {
        $result = $this->config->ensureDirExists('other_key', '/some/path');

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function ensureDirExistsReturnsNullForTildeValue(): void
    {
        $result = $this->config->ensureDirExists('TERMINUS_CACHE_DIR', '~');

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function extendMergesConfigFromAnother(): void
    {
        $otherConfig = new TerminusConfig();
        $otherConfig->set('extended_key', 'extended_value');

        $this->config->extend($otherConfig);

        $this->assertEquals('extended_value', $this->config->get('extended_key'));
    }

    /**
     * @test
     */
    public function getReplacesPlaceholders(): void
    {
        $this->config->set('base_path', '/base');
        $this->config->set('full_path', '[[TERMINUS_BASE_PATH]]/subdir');

        $result = $this->config->get('full_path');

        // The placeholder should be replaced
        $this->assertStringContainsString('base', $result);
        $this->assertStringContainsString('subdir', $result);
    }

    /**
     * @test
     */
    public function getReturnsDefaultOverrideWhenKeyNotSet(): void
    {
        $result = $this->config->get('nonexistent_key', 'default_value');

        $this->assertEquals('default_value', $result);
    }
}
