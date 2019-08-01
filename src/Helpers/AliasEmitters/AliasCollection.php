<?php
/**
 * Collect aliases.
 *
 * This class collects aliases, which may be provided in any order.
 * It then sorts them and returns the result when requested.
 */

namespace Pantheon\Terminus\Helpers\AliasEmitters;

/**
 * Collect aliases
 */
class AliasCollection
{
    /** @var EnvironmentCollection[] */
    protected $aliases = [];

    /**
     * Add an alias to the collection.
     *
     * @param AliasData $alias
     *   Alias to add.
     * @return $this
     */
    public function add(AliasData $alias)
    {
        $environmentCollection = $this->getCollection($alias->siteName());
        $environmentCollection->add($alias);

        return $this;
    }

    /**
     * Return a sorted list of aliases.
     *
     * @return EnvironmentCollection[]
     */
    public function all()
    {
        uksort($this->aliases, 'strnatcmp');
        return $this->aliases;
    }

    /**
     * Return the number of aliases in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->aliases);
    }

    /**
     * Create a collection of environments and attaches it to the specified alias.
     */
    protected function getCollection($name)
    {
        if (!isset($this->aliases[$name])) {
            $environmentCollection = new EnvironmentCollection();
            $this->aliases[$name] = $environmentCollection;
        }
        return $this->aliases[$name];
    }
}
