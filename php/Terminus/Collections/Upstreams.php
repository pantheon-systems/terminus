<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusNotFoundException;
use Terminus\Session;

class Upstreams extends TerminusCollection
{
    /**
     * @var string
     */
    protected $collected_class = 'Terminus\Models\Upstream';
    /**
     * @var string
     */
    protected $url = 'products';

    /**
     * Object constructor
     *
     * @param array $options Options to set as $this->key
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->user = Session::getUser();
    }


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

    /**
     * Adds a model to this collection
     *
     * @param object $model_data Data to feed into attributes of new model
     * @param array  $options    Data to make properties of the new model
     * @return void
     */
    public function add($model_data, array $options = [])
    {
        parent::add($model_data->attributes, $options);
    }
}
