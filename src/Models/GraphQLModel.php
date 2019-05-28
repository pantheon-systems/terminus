<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Request\GraphQLRequest;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class GraphQLModel
 * @package Pantheon\Terminus\Models
 */
abstract class GraphQLModel extends TerminusModel
{
    use ConfigAwareTrait;
    use RequestAwareTrait;

    const PRETTY_NAME = 'GraphQL Terminus model';

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
      parent::__construct($attributes, $options);
      $this->setRequest();
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
}
