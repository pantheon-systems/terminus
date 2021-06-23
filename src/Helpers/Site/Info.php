<?php

namespace Pantheon\Terminus\Helpers\Site;

use Pantheon\D9ify\Traits\CommandExecutorTrait;

/**
 * Class Info
 *
 * @package D9ify\Site
 */
class Info implements InfoInterface
{

    use CommandExecutorTrait;

    /**
     * @var string
     */
    protected string $pharPath = "vendor/bin/terminus.phar";

    /**
     * @var string|null
     */
    protected ?string $id = null;

    /**
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * @var string|null
     */
    protected ?string $label = null;

    /**
     * @var string|null
     */
    protected ?string $created;

    /**
     * @var string|null
     */
    protected ?string $framework;

    /**
     * @var string|null
     */
    protected ?string $region;

    /**
     * @var string|null
     */
    protected ?string $organization;

    /**
     * @var string|null
     */
    protected ?string $plan_name;

    /**
     * @var string|null
     */
    protected ?string $upstream;

    /**
     * @var string|null
     */
    protected ?string $holder_type;

    /**
     * @var string|null
     */
    protected ?string $holder_id;

    /**
     * @var string|null
     */
    protected ?string $owner;

    /**
     * @var string|null
     */
    protected ?string $last_frozen_at;

    /**
     * @var bool|null
     */
    protected ?bool $frozen;

    /**
     * @var int|null
     */
    protected ?int $max_num_cdes;

    /**
     * Info constructor.
     *
     * @param null $site_id
     */
    public function __construct($site_id = null, $organization = null)
    {

        if ($site_id !== null && is_string($site_id)) {
            (preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
                $site_id
            ) === 1)

                ? $this->setId($site_id) : $this->setName($site_id);
        }
        $this->setOrganization($organization);
    }


    /**
     * @return bool
     * @throws \JsonException
     */
    public function refresh()
    {
        $siteinfo = $this->getPantheonSiteInfo($this->getId() ?? $this->getName());
        if ($siteinfo === null) {
            return false;
        }
        foreach ($siteinfo as $key => $value) {
            call_user_func([$this,
                "set" . str_replace(
                    " ",
                    "",
                    ucwords(str_replace("_", " ", $key))
                ),
            ], $value);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return (
            isset($this->created)
            && is_numeric($this->created)
            && $this->created !== 0
        );
    }

    /**
     * @param $site_id
     *
     * @return array|null
     * @throws \JsonException
     *
     * @example
     * Array (
     *   [id] => XXXXXXXX-e717-4144-ac38-XXXXXXXXXXXX
     *   [name] => my-drupal-8.x-site
     *   [label] => Drupal 8.x site
     *   [created] => 1569515403
     *   [framework] => drupal8
     *   [region] => United States
     *   [organization] => XXXXXXXX-e717-4144-ac38-XXXXXXXXXXXX
     *   [plan_name] => Performance Xlarge
     *   [max_num_cdes] => 10
     *   [upstream] => XXXXXXXX-e717-4144-ac38-XXXXXXXXXXXX:
     *   git://github.com/pantheon-systems/drops-8.git
     *   [holder_type] => organization
     *   [holder_id] => XXXXXXXX-e717-4144-ac38-XXXXXXXXXXXX
     *   [owner] => XXXXXXXX-e717-4144-ac38-XXXXXXXXXXXX
     *   [frozen] =>
     *   [last_frozen_at] =>
     * )
     */

    protected function getPantheonSiteInfo($site_id): ?array
    {
        $this->execute('%s site:info %s --format=json', [
            "vendor/bin/terminus.phar",
            $site_id
        ]);

        if ($this->getLastStatus() !== 0) {
            return null;
        }
        return json_decode(
            join("", $this->getExecResult()),
            true,
            5,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(?string $id = null): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getRef(): ?string
    {
        return $this->getId() ?? $this->getName();
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name = null): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getCreated(): ?string
    {
        return $this->created;
    }

    /**
     * @param string $created
     */
    public function setCreated(?string $created): void
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getFramework(): ?string
    {
        return $this->framework;
    }

    /**
     * @param string $framework
     */
    public function setFramework(?string $framework): void
    {
        $this->framework = $framework;
    }

    /**
     * @return string
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion(?string $region): void
    {
        $this->region = $region;
    }

    /**
     * @return string
     */
    public function getPlanName(): ?string
    {
        return $this->plan_name;
    }

    /**
     * @param string $plan_name
     */
    public function setPlanName(?string $plan_name): void
    {
        $this->plan_name = $plan_name;
    }

    /**
     * @return int
     */
    public function getMaxNumCdes(): ?int
    {
        return $this->max_num_cdes;
    }

    /**
     * @param int $max_num_cdes
     */
    public function setMaxNumCdes(?int $max_num_cdes): void
    {
        $this->max_num_cdes = $max_num_cdes;
    }

    /**
     * @return string
     */
    public function getUpstream(): ?string
    {
        return $this->upstream;
    }

    /**
     * @param string $upstream
     */
    public function setUpstream(?string $upstream): void
    {
        $this->upstream = $upstream;
    }

    /**
     * @return string
     */
    public function getHolderType(): ?string
    {
        return $this->holder_type;
    }

    /**
     * @param string $holder_type
     */
    public function setHolderType(?string $holder_type): void
    {
        $this->holder_type = $holder_type;
    }

    /**
     * @return string
     */
    public function getHolderId(): ?string
    {
        return $this->holder_id;
    }

    /**
     * @param string $hold_id
     */
    public function setHolderId(?string $holder_id): void
    {
        $this->holder_id = $holder_id;
    }

    /**
     * @return string
     */
    public function getOwner(): ?string
    {
        return $this->owner;
    }

    /**
     * @param string $owner
     */
    public function setOwner(?string $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return bool
     */
    public function isFrozen(): ?bool
    {
        return $this->frozen;
    }

    /**
     * @param bool $frozen
     */
    public function setFrozen(?bool $frozen): void
    {
        $this->frozen = $frozen;
    }

    /**
     * @return string
     */
    public function getLastFrozenAt(): ?string
    {
        return $this->last_frozen_at;
    }

    /**
     * @param string $last_frozen_at
     */
    public function setLastFrozenAt(?string $last_frozen_at = null): void
    {
        $this->last_frozen_at = $last_frozen_at;
    }

    /**
     * @return bool
     */
    public function create(): bool
    {
        $org_switch = "";
        $name = $this->getName();
        if (!is_string($name) || empty($name)) {
            throw new \Exception(
                "Not enough information provided to create the site." .
                "You must set the NAME property and optionally the LABEL "
            );
        }
        $org = $this->getOrganization();
        if ($org !== null) {
            $org_switch = sprintf(" --org=%s", $org);
        }
        $this->execute("%s %s site:create %s %s %s %s", [
            $this->pharPath,
            $name,
            $this->getLabel() ?? $name,
            "drupal9",
            $org_switch,
        ]);
        return $this->getLastStatus() === 0;
    }

    /**
     * @return string
     */
    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    /**
     * @param string $organization
     */
    public function setOrganization(?string $organization): void
    {
        $this->organization = $organization;
    }

    /**
     * @return mixed|null
     * @throws \JsonException
     */
    public function getConnectionInfo()
    {
        $result = $this->execute("%s connection:info %s.dev --format=json", [
            $this->pharPath,
            $this->getId()
        ]);
        return json_decode(join("", $result), true, 10, JSON_THROW_ON_ERROR);
    }
}
