<?php

namespace Pantheon\Terminus\Collections;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;

/**
 * Class TerminusCollection
 * @package Pantheon\Terminus\Collections
 */
abstract class TerminusCollection implements ContainerAwareInterface, RequestAwareInterface
{
    use ContainerAwareTrait;
    use RequestAwareTrait;

    /**
     * @var string
     */
    protected $collected_class = TerminusModel::class;
    /**
     * @var TerminusModel[]
     */
    protected $models = null;
    /**
     * @var boolean
     */
    protected $paged = false;
    /**
     * @var string
     */
    protected $url;

    /**
     * Instantiates the collection, sets param members as properties
     *
     * @param array $options Options with which to configure this collection
     */
    public function __construct(array $options = [])
    {
    }

    /**
     * Get the listing URL for this collection
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Adds a model to this collection
     *
     * @param object $model_data Data to feed into attributes of new model
     * @param array $options Data to make properties of the new model
     * @return TerminusModel
     */
    public function add($model_data, array $options = [])
    {
        $options = array_merge(
            ['id' => $model_data->id, 'collection' => $this,],
            $options
        );
        $model = $this->getContainer()->get($this->collected_class, [$model_data, $options,]);
        $this->models[$model_data->id] = $model;
        return $model;
    }

    /**
     * Retrieves all models
     * TODO: Remove automatic fetching and make fetches explicit
     *
     * @return TerminusModel[]
     */
    public function all()
    {
        return $this->getMembers();
    }

    /**
     * Fetches model data from API and instantiates its model instances
     *
     * @param array $options params to pass configure fetching
     *        array $data Data to fill in the model members of this collection
     * @return TerminusCollection $this
     */
    public function fetch(array $options = [])
    {
        $data = isset($options['data']) ? $options['data'] : $this->getCollectionData($options);
        $results = array_filter((array)$data);

        foreach ($results as $id => $model_data) {
            if (!isset($model_data->id)) {
                $model_data->id = $id;
            }
            $this->add($model_data);
        }

        return $this;
    }

    /**
     * Retrieves the model of the given ID
     *
     * @param string $id ID of desired model instance
     * @return TerminusModel $this->models[$id]
     * @throws TerminusNotFoundException
     */
    public function get($id)
    {
        foreach ($this->getMembers() as $member) {
            if (in_array($id, $member->getReferences())) {
                return $member;
            }
        }
        $class_name = $this->collected_class;
        $pretty_name = $class_name::$pretty_name;
        $particle = in_array(substr($pretty_name, 0, 1), ['a', 'e', 'i', 'o', 'u',]) ? 'an' : 'a';
        throw new TerminusNotFoundException(
            "Could not find $particle {model} identified by {id}.",
            ['model' => $pretty_name, 'id' => $id,],
            1
        );
    }

    /**
     * Determines whether the models contain an object with a specific ID
     *
     * @param string $id UUID of object to seek
     * @return boolean True if object is found, false if it is not
     */
    public function has($id)
    {
        return !is_null($models = $this->getMembers()) && array_key_exists($id, $models);
    }

    /**
     * List Model IDs
     *
     * @return string[] Array of all model IDs
     */
    public function ids()
    {
        return array_keys($this->getMembers());
    }

    /**
     * Returns an array of data where the keys are the attribute $key and the
     *   values are the attribute $value
     *
     * @param string $key Name of attribute to make array keys
     * @param mixed $value Name(s) of attribute(s) to comprise array values
     * @return array Array rendered as requested
     *         $this->attribute->$key = $this->attribute->$value
     */
    public function listing($key = 'id', $value = 'name')
    {
        $models = $this->getMembers();
        $members = array_combine(
            array_map(
                function ($member) use ($key) {
                    return $member->get($key);
                },
                $models
            ),
            array_map(
                function ($member) use ($value) {
                    if (is_scalar($value)) {
                        return $member->get($value);
                    }
                    $list = [];
                    foreach ($value as $item) {
                        $list[$item] = $member->get($item);
                    }
                    return $list;
                },
                $models
            )
        );
        return $members;
    }

    /**
     * Retrieves all models serialized into arrays.
     *
     * @return array
     */
    public function serialize()
    {
        $models = [];
        foreach ($this->getMembers() as $id => $model) {
            $models[$id] = $model->serialize();
        }
        return $models;
    }

    /**
     * Retrieves collection data from the API
     *
     * @param array $options params to pass to url request
     * @return array
     */
    protected function getCollectionData($options = [])
    {
        $args = ['options' => ['method' => 'get',],];
        if (isset($options['fetch_args'])) {
            $args = array_merge($args, $options['fetch_args']);
        }

        if ($this->paged) {
            $results = $this->request()->pagedRequest($this->getUrl(), $args);
        } else {
            $results = $this->request()->request($this->getUrl(), $args);
        }

        return $results['data'];
    }

    /**
     * Retrieves all members of this collection
     *
     * @return TerminusModel[]
     */
    protected function getMembers()
    {
        if (is_null($this->models)) {
            $this->models = [];
            $this->fetch();
        }
        return $this->models;
    }
}
