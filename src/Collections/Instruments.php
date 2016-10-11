<?php

namespace Pantheon\Terminus\Collections;

class Instruments extends UserOwnedCollection
{
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/instruments';

    /**
     * @var string
     */
    protected $collected_class = 'Terminus\Models\Instrument';
}
