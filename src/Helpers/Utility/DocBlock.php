<?php

namespace Pantheon\Terminus\Helpers\Utility;

/**
 * Parse the docblock of a function or method
 * @author Paul Scott <paul@duedil.com>
 * {@link http://www.github.com/icio/PHP-DocBlock-Parser}
 */
class DocBlock
{
    /**
     * The docblock of a class.
     * @param  String $class The class name
     * @return DocBlock
     */
    public static function ofClass($class)
    {
        return DocBlock::of(new ReflectionClass($class));
    }

    /**
     * The docblock of a class property.
     * @param  String $class    The class on which the property is defined
     * @param  String $property The name of the property
     * @return DocBlock
     */
    public static function ofProperty($class, $property)
    {
        return DocBlock::of(new ReflectionProperty($class, $property));
    }

    /**
     * The docblock of a function.
     * @param  String $function The name of the function
     * @return DocBlock
     */
    public static function ofFunction($function)
    {
        return DocBlock::of(new ReflectionFunction($function));
    }

    /**
     * The docblock of a class method.
     * @param  String $class  The class on which the method is defined
     * @param  String $method The name of the method
     * @return DocBlock
     */
    public static function ofMethod($class, $method)
    {
        return DocBlock::of(new ReflectionMethod($class, $method));
    }

    /**
     * The docblock of a reflection.
     * @param  Reflector $ref A reflector object defining `getDocComment`.
     * @return DocBlock
     */
    public static function of($ref)
    {
        if (method_exists($ref, 'getDocComment')) {
            return new DocBlock($ref->getDocComment());
        }
        return null;
    }

    /*
     * ==================================
     */

    /**
     * Tags in the docblock that have a whitepace-delimited number of parameters
     * (such as `@param type var desc` and `@return type desc`) and the names of
     * those parameters.
     *
     * @type Array
     */
    public static $vectors = array(
        'param' => ['type', 'var', 'desc'],
        'return' => ['type', 'desc'],
        'step' => ['name'],
    );

    /**
     * The description of the symbol
     * @type String
     */
    public $desc;

    /**
     * The tags defined in the docblock.
     *
     * The array has keys which are the tag names (excluding the @) and values
     * that are arrays, each of which is an entry for the tag.
     *
     * In the case where the tag name is defined in {@see DocBlock::$vectors} the
     * value within the tag-value array is an array in itself with keys as
     * described by {@see DocBlock::$vectors}.
     *
     * @type Array
     */
    public $tags;

    /**
     * The entire DocBlock comment that was parsed.
     * @type String
     */
    public $comment;

    /**
     * CONSTRUCTOR.
     * @param String $comment The text of the docblock
     */
    public function __construct($comment = null)
    {
        if ($comment) {
            $this->setComment($comment);
        }
    }

    /**
     * Set and parse the docblock comment.
     * @param String $comment The docblock
     */
    public function setComment($comment)
    {
        $this->desc = '';
        $this->tags = array();
        $this->comment = $comment;

        $this->parseComment($comment);
    }

    /**
     * Parse the comment into the component parts and set the state of the object.
     * @param  String $comment The docblock
     */
    protected function parseComment($comment)
    {
        // Strip the opening and closing tags of the docblock
        $comment = substr($comment, 3, -2);

        // Split into arrays of lines
        $comment = preg_split('/\r?\n\r?/', $comment);

        // Trim asterisks and whitespace from the beginning and whitespace from the end of lines
        $comment = array_map(function ($line) {
            return ltrim(rtrim($line), "* \t\n\r\0\x0B");
        }, $comment);

        // Group the lines together by @tags
        $blocks = array();
        $b = -1;
        foreach ($comment as $line) {
            if (self::isTagged($line)) {
                $b++;
                $blocks[] = array();
            } elseif ($b == -1) {
                $b = 0;
                $blocks[] = array();
            }
            $blocks[$b][] = $line;
        }

        // Parse the blocks
        foreach ($blocks as $block => $body) {
            $body = trim(implode("\n", $body));

            if ($block == 0 && !self::isTagged($body)) {
                // This is the description block
                $this->desc = $body;
                continue;
            } else {
                // This block is tagged
                $tag = substr(self::strTag($body), 1);
                $body = ltrim(substr($body, strlen($tag) + 2));

                if (isset(self::$vectors[$tag])) {
                    // The tagged block is a vector
                    $count = count(self::$vectors[$tag]);
                    if ($body) {
                        $parts = preg_split('/\s+/', $body, $count);
                    } else {
                        $parts = array();
                    }
                    // Default the trailing values
                    $parts = array_pad($parts, $count, null);
                    // Store as a mapped array
                    $this->{$tag}[] = array_combine(
                        self::$vectors[$tag],
                        $parts
                    );
                } else {
                    // The tagged block is only text
                    $this->{$tag}[] = $body;
                }
            }
        }
    }

    /**
     * Whether or not a docblock contains a given @tag.
     * @param  String $tag The name of the @tag to check for
     * @return bool
     */
    public function hasTag($tag)
    {
        return is_array($this->tags) && array_key_exists($tag, $this->tags);
    }

    /**
     * The value of a tag
     * @param  String $tag
     * @return Array
     */
    public function tag($tag)
    {
        return $this->hasTag($tag) ? $this->tags[$tag] : null;
    }

    /**
     * The value of a tag (concatenated for multiple values)
     * @param  String $tag
     * @param  string $sep The seperator for concatenating
     * @return String
     */
    public function tagImplode($tag, $sep = ' ')
    {
        return $this->hasTag($tag) ? implode($sep, $this->tags[$tag]) : null;
    }

    /**
     * The value of a tag (merged recursively)
     * @param  String $tag
     * @return Array
     */
    public function tagMerge($tag)
    {
        return $this->hasTag($tag) ? array_merge_recursive($this->tags[$tag]) : null;
    }

    /*
     * ==================================
     */

    /**
     * Whether or not a string begins with a @tag
     * @param  String $str
     * @return bool
     */
    public static function isTagged($str)
    {
        return isset($str[1]) && $str[0] == '@' && ctype_alpha($str[1]);
    }

    /**
     * The tag at the beginning of a string
     * @param  String $str
     * @return String|null
     */
    public static function strTag($str)
    {
        if (preg_match('/^@[a-z0-9_]+/', $str, $matches)) {
            return $matches[0];
        }
        return null;
    }
}
