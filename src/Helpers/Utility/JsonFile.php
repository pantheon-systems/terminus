<?php

namespace Pantheon\Terminus\Helpers\Utility;

use Composer\IO\BufferIO;
use Composer\IO\IOInterface;

/**
 * Class JsonFile.
 */
class JsonFile extends \SplFileObject
{

    /**
     * @var string
     */
    private string $original;
    /**
     * @var IOInterface|null
     */
    protected ?IOInterface $io;

    /**
     * ComposerFile constructor.
     *
     * @param $filename
     * @param string $mode
     * @param false $useIncludePath
     * @param null $context
     *
     */
    public function __construct(
        $filename,
        $openMode = "r",
        $use_include_path = false,
        $context = null,
        IOInterface $io = null
    ) {
        parent::__construct($filename, $openMode, dirname(\Composer\Factory::getComposerFile()), $context);
        $this->setIo($io ?? new BufferIO());
        if ($this->isFile()) {
            $this->original = $this->read();
            $this->unserialize($this->original);
        }
    }

    /**
     * @return IOInterface|null
     */
    public function getIo(): ?IOInterface
    {
        return $this->io;
    }

    /**
     * @param IOInterface|null $io
     */
    public function setIo(IOInterface $io): void
    {
        $this->io = $io;
    }

    /**
     * @return false|string
     */
    public function read()
    {
        return file_get_contents($this->getRealPath());
    }

    /**
     * @param string $data
     * @throws \JsonException
     */
    public function unserialize(string $serialized): void
    {
        $values = json_decode($serialized, true, 512);
        foreach ($values as $key => $value) {
            $this->{static::normalizeComposerPropertyToSetterName($key)}($value);
        }
    }

    /**
     * Translates property name into a setter name.
     *
     * @param $property
     * @return string
     * @example $blah => setBlah()
     *
     */
    public static function normalizeComposerPropertyToSetterName($property)
    {
        return "set" . str_replace(" ", "", (ucwords(str_replace("-", " ", $property))));
    }

    /**
     * @return string|null
     */
    public function getOriginal(): ?array
    {
        return json_decode($this->original, true) ?? [];
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public function __call(string $name, array $arguments)
    {
        $varName = lcfirst(substr($name, 3));
        switch (substr($name, 0, 3)) {
            case "set":
                $this->{$varName} = $arguments[0];
                break;
            case "get":
                return $this->{$varName} ?? null;
                break;

            default:
                throw new \Exception('cannot set/get requested property' . print_r(func_get_args(), true));
        }
    }

    /**
     * @throws \Exception
     */
    public function write()
    {
        return file_put_contents($this->getRealPath(), $this->serialize());
    }

    /**
     * @return string | null
     */
    public function serialize(): ?string
    {
        return $this->__toString();
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        return json_encode(
            $this->__toArray(),
            JSON_UNESCAPED_SLASHES +
            JSON_UNESCAPED_LINE_TERMINATORS +
            JSON_UNESCAPED_UNICODE +
            JSON_PRETTY_PRINT
        );
    }

    /**
     * @return array|null
     */
    public function __toArray(): ?array
    {
        $toReturn = [];
        $properties = get_object_vars($this);
        foreach ($properties as $key => $value) {
            $toReturn[$key] = $this->{static::normalizeComposerPropertyToGetterName($key)}();
        }
        return $toReturn;
    }

    /**
     * ranslates property name into a getter name.
     *
     * @param $property
     * @return string
     * @example $blah => getBlah()
     */
    public static function normalizeComposerPropertyToGetterName($property)
    {
        return "get" . str_replace(" ", "", (ucwords(str_replace("-", " ", $property))));
    }


    public function backupFile()
    {
        copy(
            $this->getRealPath(),
            dirname($this->getRealPath()) .
            sprintf("/composer-%s.json", \uniqid())
        );
    }
}
