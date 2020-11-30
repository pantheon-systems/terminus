<?php


namespace Pantheon\Terminus\Helpers\Utility;

/**
 * Class DocumentationRenderer
 *
 * @package D9ify\Utility
 */
class DocumentationRenderer
{

    /**
     * @var \D9ify\Utility\DocBlock
     */
    protected DocBlock $block;

    /**
     * DocumentationRenderer constructor.
     *
     * @param \D9ify\Utility\DocBlock $block
     */
    public function __construct(DocBlock $block)
    {
        $this->block = $block;
    }

    /**
     * @return array|null
     */
    public function serialize()
    {
        if (isset($this->block->step)) {
            $lines = [
                $this->getStep() .
                PHP_EOL . PHP_EOL .
                $this->getDescription() . PHP_EOL,
                $this->getRegex() ,
                PHP_EOL,
            ];
        }
        if (isset($this->block->name)) {
            $lines = [
                "# {$this->block->name[0]}" . PHP_EOL,
                $this->getDescription(null) . PHP_EOL,
                "## USAGE " . PHP_EOL ,
                "  ```{$this->block->usage[0]}```" . PHP_EOL ,
                "## STEPS" . PHP_EOL . PHP_EOL
            ];
        }
        return $lines ?? null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return join(PHP_EOL, $this->serialize());
    }

    public function getStep()
    {
        return "#### ===> " . $this->block->step[0]['name'];
    }

    /**
     * @param string $delimiter
     *
     * @return string|null
     */
    public function getRegex($delimiter = "       ")
    {
        if (isset($this->block->regex)) {
            $lines = [];
            foreach ($this->block->regex as $regexTag) {
                $lines += explode(PHP_EOL, $regexTag);
            }
            return join(
                PHP_EOL,
                array_map(function ($item) use ($delimiter) {
                    return $delimiter . $item;
                }, $lines)
            );
        }
        return null;
    }


    /**
     * @param string $delimiter
     *
     * @return string|null
     */
    public function getDescription($delimiter = "   ") : ?string
    {
        if (isset($this->block->description)) {
            $lines = [];
            foreach ($this->block->description as $descriptionTag) {
                $lines += explode(PHP_EOL, $descriptionTag);
            }
            return join(
                PHP_EOL,
                array_map(function ($item) use ($delimiter) {
                        return $delimiter . $item;
                }, $lines)
            );
        }
        return null;
    }
}
