<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class TerminusModel
 *
 * @package Pantheon\Terminus\Models
 */
abstract class TerminusModel implements
    ConfigAwareInterface,
    RequestAwareInterface
{
    use ConfigAwareTrait;
    use RequestAwareTrait;

    public const PRETTY_NAME = 'terminus model';

    /**
     * @var array
     */
    public static array $date_attributes = [];

    /**
     * @var string|null
     */
    public ?string $id = null;

    /**
     * @var object
     */
    protected object $attributes;

    /**
     * @var TerminusCollection|null
     */
    protected ?TerminusCollection $collection = null;

    /**
     * @var string The URL at which to fetch this model's information
     */
    protected string $url = '';

    /**
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options with which to configure this model
     */
    public function __construct(mixed $attributes = null, array $options = [])
    {
        $this->attributes = (object)[];
        if (isset($options['collection'])) {
            $this->collection = $options['collection'];
        }
        if (is_object($attributes)) {
            $this->attributes = $this->parseAttributes($attributes);
            if (isset($this->attributes->id)) {
                $this->id = (string) $this->attributes->id;
            }
        }
    }

    /**
     * Fetches this object from Pantheon
     *
     * @param array $args Params to pass to request
     *
     * @return TerminusModel $this
     */
    public function fetch(array $args = []): static
    {
        $options = array_merge(['options' => ['method' => 'get']], $args);
        $results = $this->request->request($this->getUrl(), $options);
        $this->attributes = (object)array_merge(
            (array)$this->attributes,
            (array)$this->parseAttributes($results->getData())
        );
        return $this;
    }

    /**
     * Retrieves attribute of given name
     *
     * @param string $attribute Name of the key of the desired attribute
     *
     * @return mixed Value of the attribute, or null if not set.
     */
    public function get(string $attribute): mixed
    {
        return $this->has($attribute) ? $this->attributes->$attribute : null;
    }

    /**
     * Returns the fields by which this model can be found.
     *
     * @return array
     */
    public function getReferences(): array
    {
        return [$this->id,];
    }

    /**
     * Get the URL for this model
     *
     * @return string
     */
    public function getUrl(): string
    {
        return str_replace('{id}', $this->id ?? '', $this->url);
    }

    /**
     * Checks whether the model has an attribute
     *
     * @param string $attribute Name of the attribute key
     *
     * @return boolean True if attribute exists, false otherwise
     */
    public function has(string $attribute): bool
    {
        return isset($this->attributes->$attribute);
    }

    /**
     * Formats the object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize(): array
    {
        return (array)$this->attributes;
    }

    /**
     * Sets an attribute
     *
     * @param string $attribute Name of the attribute key
     * @param mixed $value The value to assign to the attribute
     */
    public function set(string $attribute, mixed $value): void
    {
        $this->attributes->$attribute = $value;
    }

    /**
     * Unsets an attribute
     *
     * @param string $attribute Name of the attribute key
     */
    public function unsetAttribute(string $attribute): void
    {
        unset($this->attributes->$attribute);
    }

    /**
     * Modify response data between fetch and assignment
     *
     * @param object $data attributes received from API response
     *
     * @return object $data
     */
    protected function parseAttributes(object $data): object
    {
        return $data;
    }
}
