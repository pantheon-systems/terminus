<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\Upstream;

/**
 * Class Upstreams
 * @package Pantheon\Terminus\Collections
 */
class Upstreams extends UserOwnedCollection
{
    /**
     * @var string
     */
    protected $collected_class = Upstream::class;
    /**
     * @var string
     */
    protected $url = 'products';

    /**
     * Retrieves models by either upstream ID or name
     *
     * @param string $id Either an upstream ID or an upstream name
     * @return Upstream
     * @throws TerminusNotFoundException
     */
    public function get($id)
    {
        $models = $this->getMembers();
        if (isset($models[$id])) {
            return $models[$id];
        }
        foreach ($models as $model) {
            if ($model->get('longname') == $id) {
                return $model;
            }
        }
        throw new TerminusNotFoundException('An upstream identified by "{id}" could not be found.', compact('id'));
    }
}
