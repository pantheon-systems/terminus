<?php

namespace Pantheon\Terminus\Hooks;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class Authorizer
 * @package Pantheon\Terminus\Hooks
 */
class Authorizer implements ConfigAwareInterface, SessionAwareInterface
{
    use ConfigAwareTrait;
    use SessionAwareTrait;

    /**
     * Authorize the current user prior to running a command. The Annotated Commands hook manager will call this
     * function during the pre-validate phase of any command that has an 'authorize' annotation.
     *
     * @hook pre-init @authorize
     *
     * @throws TerminusException
     */
    public function ensureLogin()
    {
        if (!$this->session()->isActive()) {
            $tokens_obj = $this->session()->getTokens();
            $all_tokens = $tokens_obj->all();
            if (!empty($user = $this->getConfig()->get('user'))) {
                $token = $this->isEmailPattern($user)
                    ? $this->matchTokenByPattern($all_tokens, $user)
                    : $tokens_obj->get($user);
            } elseif (count($all_tokens) == 1) {
                $token = array_shift($all_tokens);
            } else {
                throw new TerminusException(
                    'You are not logged in. Run `auth:login` to authenticate or `help auth:login` for more info.'
                );
            }
            $token->logIn();
        }
    }

    /**
     * Returns true if $value is a wildcard pattern rather than a full email address.
     * Patterns contain '*' or start with '@' (shorthand for '*@domain.com').
     */
    private function isEmailPattern(string $value): bool
    {
        return str_contains($value, '*') || str_starts_with($value, '@');
    }

    /**
     * Filters $tokens by fnmatch() against $pattern and returns the single match.
     *
     * @param array $tokens All saved token models, keyed by email.
     * @param string $pattern A wildcard pattern such as '*@domain.com' or '@domain.com'.
     * @throws TerminusException If zero or more than one token matches.
     */
    private function matchTokenByPattern(array $tokens, string $pattern): \Pantheon\Terminus\Models\SavedToken
    {
        // Normalize @domain.com → *@domain.com
        if (str_starts_with($pattern, '@')) {
            $pattern = '*' . $pattern;
        }

        $matches = array_filter($tokens, fn($token) => fnmatch($pattern, $token->id));

        if (count($matches) === 1) {
            return array_shift($matches);
        }

        if (count($matches) === 0) {
            throw new TerminusException(
                'No saved token matches "{pattern}". Run `auth:login` to add one.',
                ['pattern' => $pattern]
            );
        }

        throw new TerminusException(
            '{n} saved tokens match "{pattern}". Set TERMINUS_USER to a full email address to disambiguate.',
            ['n' => count($matches), 'pattern' => $pattern]
        );
    }
}
