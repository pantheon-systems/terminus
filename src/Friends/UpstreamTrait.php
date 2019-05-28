<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\SiteUpstream;
use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Models\Upstream;

/**
 * Class UpstreamTrait
 * @package Pantheon\Terminus\Friends
 */
trait UpstreamTrait
{
    /**
     * @var [Upstream|SiteUpstream]
     */
    private $upstream;

    /**
     * @inheritdoc
     */
    public function __construct($attributes, array $options = [])
    {
        if (isset($options['upstream'])) {
            $this->setUpstream($options['upstream']);
        }
        parent::__construct($attributes, $options);
    }

    /**
     * @return [Upstream|SiteUpstream] Returns a Upstream-type object
     */
    public function getUpstream()
    {
        return $this->upstream;
    }

    /**
     * @param [Upstream|SiteUpstream] $upstream
     */
    public function setUpstream(TerminusModel $upstream)
    {
        if (!in_array(get_class($upstream), [SiteUpstream::class, Upstream::class])) {
            throw new \TypeError(
                'Argument passed to ' . __CLASS__ . '::' . __FUNCTION__ . ' must be type ' . SiteUpstream::class . ' or ' . Upstream::class . '. Received class ' . get_class($upstream) . '.'
            );
        }
        $this->upstream = $upstream;
    }
}
