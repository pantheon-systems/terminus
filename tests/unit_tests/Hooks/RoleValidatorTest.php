<?php

namespace Pantheon\Terminus\UnitTests\Hooks;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Hooks\RoleValidator;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class RoleValidatorTest
 * Testing class for Pantheon\Terminus\Hooks\RoleValidator
 * @package Pantheon\Terminus\UnitTests\Hooks
 */
class RoleValidatorTest extends \PHPUnit_Framework_TestCase
{
    const ORG_ROLES = 'admin, developer, team_member, or unprivileged';
    const PARAM_NAME = 'role';
    const SITE_ROLES = 'developer or team_member';
    /**
     * @var AnnotationData
     */
    protected $annotation_data;
    /**
     * @var CommandData
     */
    protected $command_data;
    /**
     * @var TerminusException
     */
    protected $exception;
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var RoleValidator
     */
    protected $role_validator;

    /**
     * @inheritDoc
     */
    public function setUp()
    {
        $this->config = new TerminusConfig();

        $this->annotation_data = $this->getMockBuilder(AnnotationData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->command_data = $this->getMockBuilder(CommandData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->exception = $this->getMockBuilder(TerminusException::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->input = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command_data->expects($this->once())
            ->method('input')
            ->with()
            ->willReturn($this->input);

        $this->role_validator = new RoleValidator();
    }

    /**
     * Tests validateRole for a command without a role parameter
     */
    public function testValidateRoleNoRole()
    {
        $this->expectHasArgument(false);
        $this->expectCommandName("doesn't matter", false);
        $this->expectGetArgument("doesn't matter", false);

        $this->role_validator->validateRole($this->command_data);
    }

    /**
     * Tests validateRole for an org command with an invalid role parameter
     */
    public function testValidateRoleOrgInvalid()
    {
        $invalid_role = 'invalid';

        $this->expectHasArgument();
        $this->expectCommandName('org:whatever:idk');
        $this->expectGetArgument($invalid_role);
        $this->expectInvalidRoleException($invalid_role, self::ORG_ROLES);

        $this->role_validator->validateRole($this->command_data);
    }

    /**
     * Tests validateRole for an org command with a valid role parameter
     */
    public function testValidateRoleOrgValid()
    {
        $valid_role = 'admin';

        $this->expectHasArgument();
        $this->expectCommandName('org:whatever:idk');
        $this->expectGetArgument($valid_role);

        $this->role_validator->validateRole($this->command_data);
    }

    /**
     * Tests validateRole for a site command with an invalid role parameter
     */
    public function testValidateRoleSiteInvalid()
    {
        $invalid_role = 'admin'; // This one is valid for org commands but not site ones

        $this->expectHasArgument();
        $this->expectCommandName('site:omg:wth');
        $this->expectGetArgument($invalid_role);
        $this->expectInvalidRoleException($invalid_role, self::SITE_ROLES);

        $this->role_validator->validateRole($this->command_data);
    }

    /**
     * Tests validateRole for a site command with a valid role parameter
     */
    public function testValidateRoleSiteValid()
    {
        $valid_role = 'team_member';

        $this->expectHasArgument();
        $this->expectCommandName('site:omg:wth');
        $this->expectGetArgument($valid_role);

        $this->role_validator->validateRole($this->command_data);
    }

    /**
     * Tests validateRole for a command that doesn't have a role list
     */
    public function testValidateRoleWrongCommand()
    {
        $invalid_command = 'user:wrong:cmd';

        $this->expectHasArgument();
        $this->expectCommandName($invalid_command);
        $this->expectGetArgument("doesn't matter", false);
        $this->expectInvalidCommandNameException($invalid_command);

        $this->role_validator->validateRole($this->command_data);
    }

    /**
     * Sets up the test to expect the input to have the argument or not
     *
     * @param bool $expect
     */
    protected function expectHasArgument($expect = true)
    {
        $this->input->expects($this->once())
            ->method('hasArgument')
            ->with(self::PARAM_NAME)
            ->willReturn($expect);
    }

    /**
     * Sets up the test to expect the input to have the argument or not
     *
     * @param bool $expect
     */
    protected function expectCommandName($command_name, $expect = true)
    {
        $expectation = $expect ? $this->once() : $this->any();

        $this->command_data->expects($expectation)
            ->method('annotationData')
            ->with()
            ->willReturn($this->annotation_data);
        $this->annotation_data->method('get')
            ->with('command')
            ->willReturn($command_name);
    }

    /**
     * Sets up the test to expect the input to have the argument or not
     *
     * @param bool $expect
     */
    protected function expectGetArgument($value, $expect = true)
    {
        $expectation = $expect ? $this->once() : $this->never();

        $this->input->expects($expectation)
            ->method('getArgument')
            ->with(self::PARAM_NAME)
            ->willReturn($value);
    }

    /**
     * Expects an invalid-command exception to be thrown
     *
     * @param $command_name Name of the command whose parameter is being validated
     */
    protected function expectInvalidCommandNameException($command_name)
    {
        $expected_exception = new TerminusException(
            'The available roles for {command_name} are unknown.',
            compact('command_name')
        );
        $this->setExpectedException(get_class($expected_exception), $expected_exception->getMessage());
    }

    /**
     * Expects an invalid-role exception to be thrown
     *
     * @param $role
     * @param $roles
     */
    protected function expectInvalidRoleException($role, $roles)
    {
        $expected_exception = new TerminusException(
            '{role} is not a valid role selection. Please enter {roles}.',
            compact('role', 'roles')
        );
        $this->setExpectedException(get_class($expected_exception), $expected_exception->getMessage());
    }
}
