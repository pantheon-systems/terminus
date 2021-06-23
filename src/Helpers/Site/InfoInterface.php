<?php

namespace Pantheon\Terminus\Helpers\Site;

interface InfoInterface
{

  /**
   * @return string
   */
    public function getId(): ?string;

  /**
   * @param string $id
   */
    public function setId(string $id = null): void;

  /**
   * @return string
   */
    public function getName(): ?string;

  /**
   * @param string $name
   */
    public function setName(?string $name = null): void;

  /**
   * @return string
   */
    public function getLabel(): ?string;

  /**
   * @param string $label
   */
    public function setLabel(?string $label): void;

  /**
   * @return string
   */
    public function getCreated(): ?string;

  /**
   * @param string $created
   */
    public function setCreated(?string $created): void;

  /**
   * @return string
   */
    public function getFramework(): ?string;

  /**
   * @param string $framework
   */
    public function setFramework(?string $framework): void;

  /**
   * @return string
   */
    public function getRegion(): ?string;

  /**
   * @param string $region
   */
    public function setRegion(?string $region): void;

  /**
   * @return string
   */
    public function getOrganization(): ?string;

  /**
   * @param string $organization
   */
    public function setOrganization(?string $organization): void;

  /**
   * @return string
   */
    public function getPlanName(): ?string;

  /**
   * @param string $plan_name
   */
    public function setPlanName(?string $plan_name): void;

  /**
   * @return int
   */
    public function getMaxNumCdes(): ?int;

  /**
   * @param int $max_num_cdes
   */
    public function setMaxNumCdes(?int $max_num_cdes): void;

  /**
   * @return string
   */
    public function getUpstream(): ?string;

  /**
   * @param string $upstream
   */
    public function setUpstream(?string $upstream): void;

  /**
   * @return string
   */
    public function getHolderType(): ?string;

  /**
   * @param string $holder_type
   */
    public function setHolderType(?string $holder_type): void;

  /**
   * @return string
   */
    public function getHolderId(): ?string;

  /**
   * @param string $hold_id
   * @param string $hold_id
   */
    public function setHolderId(?string $holder_id): void;

  /**
   * @return string
   */
    public function getOwner(): ?string;

  /**
   * @param string $owner
   */
    public function setOwner(?string $owner): void;

  /**
   * @return bool
   */
    public function isFrozen(): ?bool;

  /**
   * @param bool $frozen
   */
    public function setFrozen(?bool $frozen): void;

  /**
   * @return string
   */
    public function getLastFrozenAt(): ?string;

  /**
   * @param string $last_frozen_at
   */
    public function setLastFrozenAt(?string $last_frozen_at = null): void;
}
