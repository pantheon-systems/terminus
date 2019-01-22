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
     * @var array
     */
    private $data = [];
    /**
     * @var string
     */
    protected $collected_class = TerminusModel::class;
    /**
     * @var TerminusModel[]
     */
    protected $models = null;

    /**
     * Instantiates the collection, sets param members as properties
     *
     * @param array $options Options with which to configure this collection
     */
    public function __construct(array $options = [])
    {
        if (isset($options['data'])) {
            $this->setData($options['data']);
        }
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
     *
     * @return TerminusModel[]
     */
    public function all()
    {
        if (is_null($this->models)) {
            $this->models = [];
            $this->fetch();
        }
        return $this->models;
    }

    /**
     * Fetches model data from API and instantiates its model instances
     *
     * @return TerminusCollection $this
     */
    public function fetch()
    {
        foreach ($this->getData() as $id => $model_data) {
            if (!isset($model_data->id)) {
                $model_data->id = $id;
            }
            $this->add($model_data);
        }
        return $this;
    }

    /**
     * Filters the members of this collection
     *
     * @param callable $filter Filter function
     */
    public function filter(callable $filter)
    {
        $this->models = array_filter($this->all(), $filter);
        return $this;
    }

    /**
     * Filters the models by a regex checked against a specific attribute
     *
     * @param string $attribute Name of the attribute to apply the regex filter to
     * @param string $regex Non-delimited PHP regex to filter site names by
     * @return TerminusCollection
     */
    public function filterByRegex($attribute, $regex = '(.*)')
    {
        return $this->filter(function ($model) use ($attribute, $regex) {
            preg_match("~$regex~", $model->get($attribute), $matches);
            return !empty($matches);
        });
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
        foreach ($this->all() as $member) {
            if (in_array($id, $member->getReferences())) {
                return $member;
            }
        }
        $class_name = $this->collected_class;
        $pretty_name = $class_name::PRETTY_NAME;
        $particle = in_array(substr($pretty_name, 0, 1), ['a', 'e', 'i', 'o', 'u',]) ? 'an' : 'a';
        throw new TerminusNotFoundException(
            "Could not find $particle {model} identified by {id}.",
            ['model' => $pretty_name, 'id' => $id,],
            1
        );
    }

    /**
     * Returns the name of the model class this collection collects
     *
     * @return string
     */
    public function getCollectedClass()
    {
        return $this->collected_class;
    }

    /**
     * @return array Returns data array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Determines whether the models contain an object with a specific ID
     *
     * @param string $id UUID of object to seek
     * @return boolean True if object is found, false if it is not
     */
    public function has($id)
    {
        return !is_null($models = $this->all()) && array_key_exists($id, $models);
    }

    /**
     * List Model IDs
     *
     * @return string[] Array of all model IDs
     */
    public function ids()
    {
        return array_keys($this->all());
    }

    /**
     * Resets the model array for reprocessing of the collection data
     *
     * @return $this
     */
    public function reset()
    {
        $this->models = null;
        return $this;
    }

    /**
     * Retrieves all models serialized into arrays.
     *
     * @return array
     */
    public function serialize()
    {
        $models = [];
        foreach ($this->all() as $id => $model) {
            $models[$id] = $model->serialize();
        }
        return $models;
    }

    /**
     * @param array $data
     */
    public function setData(array $data = [])
    {
        $this->data = $data;
    }
}
