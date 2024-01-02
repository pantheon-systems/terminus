<?php

namespace Pantheon\Terminus\Collections;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class TerminusCollection
 * @package Pantheon\Terminus\Collections
 */
abstract class TerminusCollection implements ContainerAwareInterface, RequestAwareInterface, LoggerAwareInterface
{
    use ContainerAwareTrait;
    use RequestAwareTrait;
    use LoggerAwareTrait;

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
        if (is_string($model_data)) {
            throw new TerminusException($model_data);
        }
        $options = array_merge(
            ['id' => $model_data->id, 'collection' => $this],
            $options
        );
        $nickname = \uniqid($model_data->id);

        $this->getContainer()->add($nickname, $this->collected_class)
            ->addArguments([$model_data, $options]);
        $model = $this->getContainer()->get($nickname);
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
            if (!$id && !is_object($model_data)) {
                // Empty model, just skip it.
                continue;
            }
            if (!is_object($model_data)) {
                // This should always be an object, however occasionally it is returning as a string
                // We need more information about what it is and to handle the error
                $model_data_str = print_r($model_data, true);
                $error_maxlength = 250;
                if (is_string($model_data_str) && strlen($model_data_str) > $error_maxlength) {
                    $model_data_str = substr($model_data_str, 0, $error_maxlength) . ' ...';
                }
                $error_message = "Fetch failed {file}:{line} \$model_data expected as object but returned as {type}.";
                $error_message .= "\nUnexpected value: {model_data_str}";
                $trace = debug_backtrace();
                $context = [
                    'file' => $trace[0]['file'],
                    'line' => $trace[0]['line'],
                    'type' => gettype($model_data),
                    'model_data_str' => $model_data_str
                ];

                // verbose logging for debugging
                $this->logger->debug($error_message, $context);

                // less information for more user-facing messages, but a problem has occurred and we're skipping this
                // item so we should still surface a user-facing message
                $this->logger->warning("Model data missing for {id}", ['id' => $id,]);

                // skip this item since it lacks useful data
                continue;
            }
            if (!isset($model_data->id)) {
                $model_data->id = $id;
            }
            $this->add($model_data);
        }
        return $this;
    }

    /**
     * Filters the members of this collectin
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
    public function get($id): TerminusModel
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
            ['model' => $pretty_name, 'id' => $id,]
        );
    }

    /**
     * Returns the name of the model class this collection collects
     *
     * @return string
     */
    public function getCollectedClass(): string
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
     * Determines whether the models contain any object provided a list of IDs
     *
     * @param array $ids Ids of object to seek
     * @return boolean True if object is found, false if it is not
     */
    public function containsAny($ids)
    {
        $ids = array_flip($ids);
        return !is_null($models = $this->all()) && !empty(array_intersect_key($ids, $models));
    }

    /**
     * Determines whether the models contain all objects provided a list of IDs
     *
     * @param array $ids Ids of object to seek
     * @return boolean True if object is found, false if it is not
     */
    public function containsAll($ids)
    {
        return !is_null($models = $this->all()) && empty(array_diff($ids, array_keys($models)));
    }

    /**
     * Determines whether the models contain no objects.
     *
     * @return boolean False if object is found, True if it is not
     */
    public function containsNone()
    {
        return !is_null($models = $this->all()) && empty($models);
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

    /**
     * Convert comma-separated string into an array.
     * @param string $input
     * @return array
     */
    public function splitString(string $input = "")
    {
        /**
         * array_map to trim each item
         * array_filter to always return an array, even if empty
         */
        return array_filter(array_map('trim', explode(',', $input)));
    }
}
