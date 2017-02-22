<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;

/**
 * Class TerminusModel
 * @package Pantheon\Terminus\Models
 */
abstract class TerminusModel implements RequestAwareInterface
{
    use RequestAwareTrait;

    public static $pretty_name = 'terminus model';
    /**
     * @var string
     */
    public $id;
    /**
     * @var object
     */
    protected $attributes;
    /**
     * @var TerminusCollection
     */
    protected $collection;
    /**
     * @var string The URL at which to fetch this model's information
     */
    protected $url;

    /**
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options with which to configure this model
     */
    public function __construct($attributes = null, array $options = [])
    {
        if (isset($options['collection'])) {
            $this->collection = $options['collection'];
        }
        if (is_object($attributes)) {
            $this->attributes = $this->parseAttributes($attributes);
            if (isset($this->attributes->id)) {
                $this->id = $this->attributes->id;
            }
        } else {
            $this->attributes = (object)[];
        }
    }

    /**
     * Fetches this object from Pantheon
     *
     * @param array $args Params to pass to request
     * @return TerminusModel $this
     */
    public function fetch(array $args = [])
    {
        $options = array_merge(['options' => ['method' => 'get',],], $args);
        $results = $this->request->request($this->getUrl(), $options);
        $this->attributes = (object)array_merge(
            (array)$this->attributes,
            (array)$this->parseAttributes($results['data'])
        );
        return $this;
    }

    /**
     * Retrieves attribute of given name
     *
     * @param string $attribute Name of the key of the desired attribute
     * @return mixed Value of the attribute, or null if not set.
     */
    public function get($attribute)
    {
        return $this->has($attribute) ? $this->attributes->$attribute : null;
    }

    /**
     * Checks whether the model has an attribute
     *
     * @param string $attribute Name of the attribute key
     * @return boolean True if attribute exists, false otherwise
     */
    public function has($attribute)
    {
        return isset($this->attributes->$attribute);
    }

    /**
     * Sets an attribute
     *
     * @param string $attribute Name of the attribute key
     * @param mixed $value The value to assign to the attribute
     */
    public function set($attribute, $value)
    {
        $this->attributes->$attribute = $value;
    }

    /**
     * Returns the fields by which this model can be found.
     *
     * @return array
     */
    public function getReferences()
    {
        return [$this->id,];
    }

    /**
     * Get the URL for this model
     *
     * @return string
     */
    public function getUrl()
    {
        return str_replace('{id}', $this->id, $this->url);
    }

    /**
     * Formats the object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        return (array)$this->attributes;
    }

    /**
     * Modify response data between fetch and assignment
     *
     * @param object $data attributes received from API response
     * @return object $data
     */
    protected function parseAttributes($data)
    {
        return $data;
    }
}
