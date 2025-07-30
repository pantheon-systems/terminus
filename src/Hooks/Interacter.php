<?php

namespace Pantheon\Terminus\Hooks;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Robo\Contract\ConfigAwareInterface;
use Consolidation\AnnotatedCommand\AnnotationData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Symfony\Component\Console\Input\InputArgument;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;

/**
 * Class Interacter
 * @package Pantheon\Terminus\Hooks
 */
class Interacter implements ConfigAwareInterface, ContainerAwareInterface, SessionAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use SessionAwareTrait;

    /**
     * Gets arguments and options in an interactive way.
     * The Annotated Commands hook manager will call this function during the interact phase
     * of any command that has an 'interact' annotation.
     *
     * Possible tag values:
     * - only-required-arguments: Only required arguments will be asked for. (default)
     * - all-arguments: All arguments will be asked for.
     * - all: All arguments and options will be asked for.
     *
     * Also configurable through the 'interact-options-exclude' tag to exclude presenting options for the excluded arguments/options.
     *
     * @hook interact @interact
     *
     * @throws TerminusException
     */
    public function interact(InputInterface $input, OutputInterface $output, AnnotationData $annotationData)
    {
        if (!$this->isInteractive($input)) {
            // If we are not in interactive mode, then nothing to do.
            return;
        }

        $command_name = $annotationData->get('command');
        if (empty($command_name)) {
            // Nothing to do if no command name is provided.
            return;
        }

        $interact_type = $annotationData->get('interact');
        if (empty($interact_type)) {
            // Default to only-required-arguments if no interact type is provided.
            $interact_type = 'only-required-arguments';
        }
        if (!in_array($interact_type, ['only-required-arguments', 'all-arguments', 'all'])) {
            throw new TerminusException('Invalid interact type: {type}', ['type' => $interact_type]);
        }

        $interact_options_exclude = $annotationData->get('interact-options-exclude');
        if (!empty($interact_options_exclude)) {
            $interact_options_exclude = explode(',', $interact_options_exclude);
        }
        if (!is_array($interact_options_exclude)) {
            $interact_options_exclude = [];
        }

        $io = new SymfonyStyle($input, $output);
        $container = $this->getContainer();
        $application = $container->get('application');
        $command = $application->find($command_name);
        $definition = $command->getNativeDefinition();
        $args = $definition->getArguments();
        $options = $definition->getOptions();

        $args_to_ask = [];
        $options_to_ask = [];
        foreach ($args as $name => $argument) {
            if ($interact_type === 'only-required-arguments' && !$argument->isRequired()) {
                continue;
            }
            $args_to_ask[$name] = $argument;
        }
        if ($interact_type === 'all') {
            $options_to_ask = $options;
        }

        // Let's start with the arguments, then options.
        foreach ($args_to_ask as $name => $argument) {
            $value = $input->getArgument($name);
            if ($value !== null) {
                // If the argument is already set, skip it.
                continue;
            }
            $value = $this->ask(
                $io,
                $interact_options_exclude,
                false,
                $name,
                $argument->getDescription(),
                $argument->getDefault()
            );
            if (empty($value)) {
                continue;
            }
            $input->setArgument($name, $value);
        }

        foreach ($options_to_ask as $name => $option) {
            $value = $input->getOption($name);
            if ($value !== null) {
                // If the option is already set, skip it.
                continue;
            }
            $value = $this->ask(
                $io,
                $interact_options_exclude,
                true,
                $name,
                $option->getDescription(),
                $option->getDefault()
            );
            if (empty($value)) {
                continue;
            }
            $input->setOption($name, $value);
        }
    }

    /**
     * Determine whether the use of a tty is appropriate.
     *
     * @return bool
     */
    public function isInteractive(InputInterface $input): bool
    {
        if ($this->getConfig()->get('disable_interactive')) {
            // If we are not in interactive mode, then nothing to do.
            return false;
        }

        if (!$input->isInteractive()) {
            // If we are not in interactive mode, then never use a tty.
            return false;
        }

        if (getenv('CI') === 'true') {
            // If we are in a CI environment, then never use a tty.
            return false;
        }

        return stream_isatty(STDIN) && stream_isatty(STDOUT);
    }

    protected function ask(
        SymfonyStyle $io,
        array $interact_options_exclude,
        bool $allow_empty,
        string $name,
        string $description,
        $default = null
    ): ?string {
        $type = $this->inferTypeFromName($name);

        switch ($type) {
            case 'string':
                return $io->ask($description, $default);
            case 'password':
                return $io->askHidden($description, $default);
            default:
                if (in_array($name, $interact_options_exclude)) {
                    // Treat it as regular string if in options exclude list.
                    return $io->ask($description, $default);
                }
                $functionName = 'get' . ucfirst($type) . 'List';
                if (method_exists($this, $functionName)) {
                    return $io->choice($description, $this->$functionName($allow_empty));
                } else {
                    throw new TerminusException('Unknown type: {type}', ['type' => $type]);
                }
        }
    }

    /**
     * Infer the type of the argument based on its name.
     *
     * @param string $name The name of the argument.
     *
     * @return string The inferred type.
     */
    public function inferTypeFromName(string $name): string
    {
        switch ($name) {
            case 'org':
            case 'organization':
                return 'organization';

            case 'upstream':
            case 'upstream_id':
                return 'upstream';

            case 'region':
            case 'preferred_zone':
                return 'region';

            case 'password':
            case 'token':
                return 'password';

            default:
                return 'string';
        }
    }

    public function getOrganizationList($allow_empty = false): array
    {
        $organizations = [];
        if ($allow_empty) {
            $organizations[''] = 'None';
        }
        $user = $this->session()->getUser();
        $orgs = $user->getOrganizationMemberships()->all();
        foreach ($orgs as $org) {
            $organization = $org->getOrganization();
            $organizations[$organization->id] = $organization->getLabel();
        }
        return $organizations;
    }

    public function getRegionList($allow_empty = false): array
    {
        $regions = [];
        if ($allow_empty) {
            $regions[''] = 'None';
        }
        $regions['us'] = 'United States';
        $regions['ca'] = 'Canada';
        $regions['eu'] = 'European Union';
        $regions['au'] = 'Australia';
        return $regions;
    }

    public function getUpstreamList($allow_empty = false): array
    {
        $upstreams = [];
        if ($allow_empty) {
            $upstreams[''] = 'None';
        }
        $user = $this->session()->getUser();
        $upstream_list = $user->getUpstreams()->all();
        foreach ($upstream_list as $upstream) {
            if ($upstream->get('type') === 'product') {
                // Skip product upstreams.
                continue;
            }
            $upstreams[$upstream->id] = $upstream->get('label');
        }
        return $upstreams;
    }
}
