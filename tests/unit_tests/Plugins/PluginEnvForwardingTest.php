<?php

namespace Pantheon\Terminus\UnitTests\Plugins;

use League\Container\Container;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\UnitTests\TerminusTestCase;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class PluginEnvForwardingTest
 *
 * Regression test to ensure that environment variables (especially COMPOSER_AUDIT_BLOCK_INSECURE)
 * are properly forwarded to Composer subprocesses during plugin installation.
 *
 * @package Pantheon\Terminus\UnitTests\Plugins
 */
class PluginEnvForwardingTest extends TerminusTestCase
{
    /**
     * @var TerminusConfig
     */
    protected $config;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var LocalMachineHelper
     */
    protected $local_machine;

    /**
     * @var array Original environment variables to restore in tearDown
     */
    protected $originalEnv = [];

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->input = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config->method('get')
            ->with($this->equalTo('timeout'))
            ->willReturn(300);
        $this->input->method('isInteractive')
            ->willReturn(false);

        $this->local_machine = new LocalMachineHelper();
        $this->local_machine->setConfig($this->config);
        $this->local_machine->setInput($this->input);
        $this->local_machine->setContainer($this->container);
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void
    {
        // Restore original environment variables
        foreach ($this->originalEnv as $var => $value) {
            if ($value === false) {
                putenv($var);
            } else {
                putenv("$var=$value");
            }
        }
        $this->originalEnv = [];

        parent::tearDown();
    }

    /**
     * Helper to set an environment variable and track it for cleanup.
     *
     * @param string $var
     * @param string|false $value
     */
    protected function setEnvVar(string $var, $value)
    {
        $original = getenv($var);
        $this->originalEnv[$var] = $original;
        if ($value === false) {
            putenv($var);
        } else {
            putenv("$var=$value");
        }
    }

    /**
     * Tests that COMPOSER_AUDIT_BLOCK_INSECURE is forwarded to the Composer process.
     *
     * This is a regression test for the bug where COMPOSER_AUDIT_BLOCK_INSECURE=0
     * was not being forwarded to the Composer subprocess during plugin installation,
     * causing Composer to block insecure packages even when the user explicitly
     * opted out via the environment variable.
     */
    public function testComposerAuditBlockInsecureIsForwarded()
    {
        // Set the environment variable that should be forwarded
        $this->setEnvVar('COMPOSER_AUDIT_BLOCK_INSECURE', '0');

        // Use reflection to access the protected getProcess method
        $reflection = new \ReflectionClass($this->local_machine);
        $getProcessMethod = $reflection->getMethod('getProcess');
        $getProcessMethod->setAccessible(true);

        // Create a process (this will internally call getForwardedEnvironment)
        $process = $getProcessMethod->invoke($this->local_machine, 'composer --version');

        // Verify that the process has the environment variable set
        // We need to use reflection to access the env property or use a different approach
        // Since Process doesn't expose getEnv(), we'll test by actually running a command
        // that echoes the env var, or we can check the process's internal state.

        // Actually, the best way is to verify that getForwardedEnvironment returns the var
        $getForwardedEnvMethod = $reflection->getMethod('getForwardedEnvironment');
        $getForwardedEnvMethod->setAccessible(true);
        $forwardedEnv = $getForwardedEnvMethod->invoke($this->local_machine);

        $this->assertArrayHasKey(
            'COMPOSER_AUDIT_BLOCK_INSECURE',
            $forwardedEnv,
            'COMPOSER_AUDIT_BLOCK_INSECURE should be in forwarded environment variables'
        );
        $this->assertEquals(
            '0',
            $forwardedEnv['COMPOSER_AUDIT_BLOCK_INSECURE'],
            'COMPOSER_AUDIT_BLOCK_INSECURE should have value "0"'
        );
    }

    /**
     * Tests that TERMINUS_FORWARD_ENV can be used to forward additional variables.
     */
    public function testTerminusForwardEnvForwardsAdditionalVars()
    {
        // Set a custom env var and specify it in TERMINUS_FORWARD_ENV
        $this->setEnvVar('MY_CUSTOM_VAR', 'my-custom-value');
        $this->setEnvVar('TERMINUS_FORWARD_ENV', 'MY_CUSTOM_VAR');

        $reflection = new \ReflectionClass($this->local_machine);
        $getForwardedEnvMethod = $reflection->getMethod('getForwardedEnvironment');
        $getForwardedEnvMethod->setAccessible(true);
        $forwardedEnv = $getForwardedEnvMethod->invoke($this->local_machine);

        $this->assertArrayHasKey(
            'MY_CUSTOM_VAR',
            $forwardedEnv,
            'MY_CUSTOM_VAR should be forwarded when specified in TERMINUS_FORWARD_ENV'
        );
        $this->assertEquals(
            'my-custom-value',
            $forwardedEnv['MY_CUSTOM_VAR'],
            'MY_CUSTOM_VAR should have the correct value'
        );
    }

    /**
     * Tests that multiple variables can be forwarded via TERMINUS_FORWARD_ENV.
     */
    public function testTerminusForwardEnvForwardsMultipleVars()
    {
        $this->setEnvVar('VAR1', 'value1');
        $this->setEnvVar('VAR2', 'value2');
        $this->setEnvVar('TERMINUS_FORWARD_ENV', 'VAR1,VAR2');

        $reflection = new \ReflectionClass($this->local_machine);
        $getForwardedEnvMethod = $reflection->getMethod('getForwardedEnvironment');
        $getForwardedEnvMethod->setAccessible(true);
        $forwardedEnv = $getForwardedEnvMethod->invoke($this->local_machine);

        $this->assertArrayHasKey('VAR1', $forwardedEnv);
        $this->assertArrayHasKey('VAR2', $forwardedEnv);
        $this->assertEquals('value1', $forwardedEnv['VAR1']);
        $this->assertEquals('value2', $forwardedEnv['VAR2']);
    }

    /**
     * Tests that Composer env vars are forwarded even when TERMINUS_FORWARD_ENV is not set.
     */
    public function testComposerVarsForwardedWithoutTerminusForwardEnv()
    {
        $this->setEnvVar('COMPOSER_AUDIT_BLOCK_INSECURE', '0');
        // Explicitly unset TERMINUS_FORWARD_ENV
        $this->setEnvVar('TERMINUS_FORWARD_ENV', false);

        $reflection = new \ReflectionClass($this->local_machine);
        $getForwardedEnvMethod = $reflection->getMethod('getForwardedEnvironment');
        $getForwardedEnvMethod->setAccessible(true);
        $forwardedEnv = $getForwardedEnvMethod->invoke($this->local_machine);

        $this->assertArrayHasKey(
            'COMPOSER_AUDIT_BLOCK_INSECURE',
            $forwardedEnv,
            'COMPOSER_AUDIT_BLOCK_INSECURE should be forwarded even without TERMINUS_FORWARD_ENV'
        );
    }

    /**
     * Tests that unset environment variables are not included in forwarded env.
     */
    public function testUnsetVarsNotForwarded()
    {
        // Don't set COMPOSER_AUDIT_BLOCK_INSECURE
        $this->setEnvVar('COMPOSER_AUDIT_BLOCK_INSECURE', false);

        $reflection = new \ReflectionClass($this->local_machine);
        $getForwardedEnvMethod = $reflection->getMethod('getForwardedEnvironment');
        $getForwardedEnvMethod->setAccessible(true);
        $forwardedEnv = $getForwardedEnvMethod->invoke($this->local_machine);

        $this->assertArrayNotHasKey(
            'COMPOSER_AUDIT_BLOCK_INSECURE',
            $forwardedEnv,
            'Unset COMPOSER_AUDIT_BLOCK_INSECURE should not be in forwarded env'
        );
    }
}
