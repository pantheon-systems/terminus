<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

class Instruments extends UserOwnedCollection
{
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/instruments';

    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\Instrument';

    /**
     * Retrieves a payment instrument object by either its UUID or its label
     *
     * @param string $id The identifier for the payment method requested
     * @return Instrument
     * @throws TerminusException When there is more than one matching instrument
     * @throws TerminusNotFoundException When there are no matching instruments
     */
    public function get($id)
    {
        $instruments = $this->models;
        if (isset($instruments[$id])) {
            return $instruments[$id];
        }
        $matches = array_filter(
            $instruments,
            function ($instrument) use ($id) {
                return ($instrument->get('label') == $id);
            }
        );
        if (empty($matches)) {
            throw new TerminusNotFoundException(
                'Could not locate an instrument identified by {id} on this account.',
                compact('id')
            );
        } else if (count($matches) > 1) {
            throw new TerminusException('More than one payment method matched {id}.', compact('id'));
        }
        return array_shift($matches);
    }
}
