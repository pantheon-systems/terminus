<?php
/**
 * EnvironmentCollection is a collection of all of the alias data for
 * all environments of a given site.
 *
 * This object collects aliases for environments, which may be provided in
 * any order. It then sorts them and returns the result when requested.
 */

namespace Pantheon\Terminus\Helpers\AliasEmitters;

/**
 * Collect aliases
 */
class EnvironmentCollection
{
    /** @var AliasData[] */
    protected $environments;

    public function __construct()
    {
        $this->environments = [];
    }

    public function add(AliasData $alias)
    {
        $this->environments[$alias->envName()] = $alias;
    }

    public function all()
    {
        uasort(
            $this->environments,
            function ($a, $b) {
                return $a->compareNames($b);
            }
        );
        return $this->environments;
    }
}
