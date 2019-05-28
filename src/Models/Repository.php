<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Friends\UpstreamInterface;
use Pantheon\Terminus\Friends\UpstreamTrait;
use Pantheon\Terminus\Request\GraphQLRequest;

/**
 * Class Repository
 * @package Pantheon\Terminus\Models
 */
class Repository extends TerminusModel implements ContainerAwareInterface, UpstreamInterface
{
    use ContainerAwareTrait;
    use UpstreamTrait;

    const PRETTY_NAME = 'repository';
    /**
     * @var array
     */
    public static $date_attributes = ['created_at', 'updated_at',];
    /**
     * @var string
     */
    protected static $query = 'query{ repository(owner: "[[owner]]", name: "[[name]]") { QUERY_FIELDS } }';
    /**
     * @var string[]
     */
    protected static $query_fields = [
        'created_at' => 'createdAt',
        'description',
        'disk_usage' => 'diskUsage',
        'fork_count' => 'forkCount',
        'is_fork' => 'isFork',
        'is_private' => 'isPrivate',
        'ssh_url' => 'sshUrl',
        'updated_at' => 'updatedAt',
    ];

    const ENDPOINT_URIS = [
        'github.com' => 'https://api.github.com/graphql',
    ];

    /**
     * @inheritDoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        if (isset($options['upstream'])) {
            $this->setUpstream($options['upstream']);
            $attributes = (object)array_merge(
                (array)$attributes,
                $this->getUpstream()->serialize()
            );
        }
        parent::__construct($attributes, $options);
    }

    /**
     * Fetches this object from the repository service
     *
     * @param array $args Params to pass to request
     * @return Repository $this
     */
    public function fetch(array $args = [])
    {
        $results = $this->getContainer()->get(GraphQLRequest::class)->request(
            $this->getUrl(),
            $this->getQuery()
        );
        $this->attributes = (object)array_merge(
            (array)$this->attributes,
            (array)$this->parseAttributes($results['data']->{self::PRETTY_NAME})
        );
        return $this;
    }

    /**
     * Get the query for this model's data
     *
     * @return string
     */
    public function getQuery()
    {
        $query = self::$query;
        //Fill in the query fields
        $query_fields = self::$query_fields;
        foreach ($query_fields as $field_label => $field_name) {
            if (!is_numeric($field_label)) {
                $query_fields[$field_label] = "$field_label: $field_name";
            }
        }
        $query = str_replace('QUERY_FIELDS', implode(' ', $query_fields), $query);

        //Replace the placeholders
        preg_match_all('/\[\[\S*\]\]/', $query, $replacements);
        $replacements = array_pop($replacements);
        foreach ($replacements as $replacement) {
            $query = str_replace(
                $replacement,
                $this->get(str_replace(['[[', ']]',], '', $replacement)),
                $query
            );
        }
        return $query;
    }

    /**
     * Get the URL for this model's service endpoint
     *
     * @return string
     */
    public function getUrl()
    {
        return self::ENDPOINT_URIS[$this->get('service')];
    }

    /**
     * @inheritDoc
     */
    protected function parseAttributes($data)
    {
        if (property_exists($data, 'url')) {
            list($_, $_, $service, $owner, $name) = explode('/', $data->url);
            $data = (object)array_merge(compact('name', 'owner', 'service'), (array)$data);
        }
        if (property_exists($data, 'repository_url')) {
            list($_, $_, $service, $owner, $name) = explode(
                '/',
                str_replace('.git', '', $data->repository_url)
            );
            $data = (object)array_merge(compact('name', 'owner', 'service'), (array)$data);
        }
        if (property_exists($data, 'product_id')) {
            unset($data->product_id);
        }
        return parent::parseAttributes($data);
    }
}
