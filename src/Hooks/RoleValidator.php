<?php

namespace Pantheon\Terminus\Hooks;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusException;

class RoleValidator
{
    const ORG_ROLES = 'admin|developer|team_member|unprivileged';
    const PARAM_NAME = 'role';
    const ROLE_SEPARATOR = '|';
    const SITE_ROLES = 'developer|team_member';

    /**
     * @hook validate *
     *
     * @param CommandData $command_data
     * @throws TerminusException If the input role is invalid
     */
    public function validateRole(CommandData $command_data)
    {
        $input = $command_data->input();
        if (!$input->hasArgument(self::PARAM_NAME)) {
            return;
        }

        $acceptable_roles = self::getRoles($command_data->annotationData()->get('command'));
        $role = $input->getArgument(self::PARAM_NAME);

        if (!in_array($role, $acceptable_roles)) {
            $replacements = [
                'role' => $role,
                'roles' => self::prettifyList($acceptable_roles)
            ];
            throw new TerminusException('{role} is not a valid role selection. Please enter {roles}.', $replacements);
        }
    }

    /**
     * Gives the roles available for a given command
     *
     * @param string $command_name The name of the command being validated
     * @return array Roles permitted for this type of command
     * @throws TerminusException if a command name is given for which there is no role list
     */
    protected static function getRoles($command_name)
    {
        $command_name_array = explode(':', $command_name);
        $command_namespace = array_shift($command_name_array);
        $const_name = 'self::' . strtoupper($command_namespace) . '_ROLES';
        if (!defined($const_name)) {
            throw new TerminusException(
                'The available roles for {command_name} are unknown.',
                compact('command_name')
            );
        }
        return explode(self::ROLE_SEPARATOR, constant($const_name));
    }

    /**
     * Turns an array into a list string using an Oxford comma
     *
     * @param array $list Array to turn into a list
     * @param string $connector Connector to use before the last item in the sentence
     * @return string
     */
    protected static function prettifyList(array $list, $connector = 'or')
    {
        $last_item = array_pop($list);
        array_push($list, "$connector $last_item");
        $glue = (count($list) > 2) ? ', ' : ' ';
        return implode($glue, $list);
    }
}
