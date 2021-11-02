<?php

namespace Pantheon\Terminus\Helpers\Composer;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Repository\RepositoryManager;
use Pantheon\Terminus\Helpers\Utility\JsonFile;
use Exception;
use JsonSchema\Validator;
use Rogervila\ArrayDiffMultidimensional;

/**
 * Class ComposerFile.
 */
class ComposerFile extends JsonFile
{

    /**
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * @var string|null
     */
    protected ?string $version = null;

    /**
     * @var string|null
     */
    protected ?string $type = null;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var array
     */
    protected array $keywords = [];

    /**
     * @var string|null
     */
    protected ?string $homepage;

    /**
     * @var string|null
     */
    protected ?string $readme = null;

    /**
     * @var DateTime|null
     */
    protected ?DateTime $time = null;

    /**
     * @var string|null
     */
    protected ?string $license;

    /**
     * @var array
     */
    protected array $authors;

    /**
     * @var
     */
    protected $support;

    /**
     * @var
     */
    protected $autoload;

    /**
     * @var
     */
    protected $autoloadDev;

    /**
     * @var
     */
    protected $includePath;

    /**
     * @var array
     */
    protected array $requirements = [];

    /**
     * @var array
     */
    protected array $devRequirements = [];

    /**
     * @var array
     */
    protected array $conflict = [];

    /**
     * @var array
     */
    protected array $replace = [];

    /**
     * @var array
     */
    protected array $provide = [];

    /**
     * @var array
     */
    protected array $extra = [];

    /**
     * @var array
     */
    protected array $suggest = [];

    /**
     * @var array
     */
    protected ?RepositoryManager $rm;

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
        parent::__construct(
            $filename,
            $openMode,
            $use_include_path,
            $context,
            $io
        );
        $this->configInit();
    }

    /**
     *
     */
    public function configInit()
    {
        $this->config = new Config();
        $this->getConfig()->setConfigSource(
            new Config\JsonConfigSource(
                new \Composer\Json\JsonFile($this->getRealPath())
            )
        );
        $orig = $this->getOriginal();
        $repos = [];
        foreach ($orig['repositories'] ?? [] as $name => $values) {
            if (is_numeric($name)) {
                if (strpos($values['url'], "drupal")) {
                    $repos['drupal'] = $values;
                    continue;
                }
                if (strpos($values['url'], "upstream")) {
                    $repos['upstream'] = $values;
                    continue;
                }
                if (strpos($values['url'], "asset-packagist")) {
                    $repos['assets'] = $values;
                    continue;
                }
                $repos[$name] = $values;
            }
        }
        $orig['repositories'] = $repos;
        $this->config->merge($orig);
        if ($this->getIo()->isDebug()) {
            $this->io->info("INFO:" . print_r($this, true));
        }
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        if (!isset($this->config)) {
            $this->configInit();
        }
        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig($values): void
    {
        if (!isset($this->config)) {
            $this->configInit();
        }
        $this->config->merge($values);
    }

    /**
     * @return null|array
     */
    public static function getSchemaRef(): array
    {
        return ['$ref' => static::getSchemaFilePath()];
    }

    /**
     *
     * @return string|null
     */
    public static function getSchemaFilePath()
    {
        return dirname(\Composer\Factory::getComposerFile()) . "/vendor/composer/composer/res/composer-schema.json";
    }

    /**
     * @return string|void
     */
    public function __toString(): string
    {
        $toReturn = json_encode(
            $this->__toArray(),
            JSON_UNESCAPED_SLASHES +
                JSON_UNESCAPED_UNICODE +
                JSON_UNESCAPED_LINE_TERMINATORS +
            JSON_UNESCAPED_SLASHES
        ) ?? "{}";
        return str_replace('\/', DIRECTORY_SEPARATOR, $toReturn);
    }

    /**
     * @throws JsonException
     */
    public function __toArray(): array
    {
        $toReturn = [];
        $schema = $this->getSchema();
        foreach ($schema->properties as $key => $value) {
            $array_value = call_user_func([
                $this,
                $this->normalizeComposerPropertyToGetterName($key),
            ]);
            if (!empty($array_value)) {
                $toReturn[$key] = $array_value;
            }
        }
        $toMerge = $this->getConfig()->raw();
        $toMerge['config'] = array_filter($toMerge['config'], function ($item) {
            return !is_null($item);
        });
        return array_merge($toReturn, $toMerge);
    }

    /**
     * @param string|null $schemaFile
     *
     * @return \stdClass
     * @throws \JsonException
     */
    public static function getSchema(string $schemaFile = null): \stdClass
    {
        return json_decode(
            file_get_contents(realpath(static::getSchemaFilePath())),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @param array $values
     */
    public function setRequire(array $values)
    {
        $this->requirements = $values;
    }

    /**
     * @param array $values
     */
    public function setRequireDev(array $values)
    {
        $this->devRequirements = $values;
    }

    /**
     * @return array
     */
    public function getRequire(): array
    {
        $toReturn = array_combine(
            array_keys($this->requirements),
            array_map(function ($item) {
                return (string)$item;
            },
            $this->requirements)
        );
        ksort($toReturn);
        return $toReturn;
    }

    /**
     * @return array
     */
    public function getRequireDev(): array
    {
        $toReturn = array_combine(
            array_keys($this->devRequirements),
            array_map(function ($item) {
                return (string)$item;
            },
            $this->devRequirements)
        );
        ksort($toReturn);
        return $toReturn;
    }

    /**
     * @return bool|void
     * @throws \Composer\Json\JsonValidationException
     * @throws \Seld\JsonLint\ParsingException
     */
    public function valid()
    {
        return $this->validateSchema();
    }

    /**
     * @param int $schema
     * @param null $schemaFile
     *
     * @return bool
     * @throws \Seld\JsonLint\ParsingException
     */
    public function validateSchema(): bool
    {
        $schema = static::getSchema();
        $schema->additionalProperties = true;
        $validator = new Validator();
        return $validator->check($this->__toArray(), $schema) ?? false;
    }

    /**
     * @throws Exception
     */
    public function getDiff()
    {
        return array_merge_recursive(
            ArrayDiffMultidimensional::compare(
                $this->getOriginal(),
                $this->__toArray()
            ),
            ArrayDiffMultidimensional::compare(
                $this->__toArray(),
                $this->getOriginal()
            )
        );
    }

    /**
     * @param $package
     * @param $version
     */
    public function addRequirement($package, $version)
    {
        // Because the destination is a known pantheon multisite, filter out
        // any acq-specific modules that might be in the composer
        if (strpos($package, 'acquia') === false) {
            if (isset($this->requirements[$package])
                && $this->requirements[$package] instanceof Requirement
            ) {
                $this->requirements[$package]->setVersionIfGreater($version);
                return;
            }
            $this->requirements[$package] = new Requirement($package, $version);
        }
    }

    /**
     * @param $package
     * @param $version
     */
    public function addDevRequirement($package, $version): void
    {
        if (isset($this->devRequirements[$package])
            && $this->devRequirements[$package] instanceof Requirement) {
            $this->devRequirements[$package] = $this->devRequirements[$package]->greaterThan($version) ?
                $this->devRequirements[$package] : new Requirement(
                    $package,
                    $version
                );
            return;
        }
        $this->devRequirements[$package] = new Requirement($package, $version);
    }

    /**
     * @param string $property
     *
     * @return \stdClass|array
     */
    public function getExtraProperty(string $property)
    {
        return $this->extra[$property] ?? [];
    }

    /**
     * @param string $property
     * @param \stdClass|array $value
     */
    public function setExtraProperty(string $property, $value)
    {
        $this->extra[$property] = $value;
    }


    /**
     * @param $values
     */
    public function setRepositories(array $values)
    {
        $this->getConfig()->merge(['repositories' => $values]);
    }

    /**
     * @return array
     */
    public function getRepositories(): array
    {
        return $this->getConfig()->raw()['repositories'];
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @param string|null $version
     */
    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }
}
