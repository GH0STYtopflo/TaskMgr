<?php

namespace ghosty\taskmgr\dto\log;

use DateTimeImmutable;
use ghosty\taskmgr\database\custom_types\ActionStatus;
use ghosty\taskmgr\database\custom_types\ResourceType;
use ghosty\taskmgr\dto\DTO;

class LogDTO extends DTO
{
    private ?int $resource_id;
    private int $user_id;
    private ResourceType $resource_type;
    private string $description;
    private ActionStatus $action_status;
    private DateTimeImmutable $timestamp;

    public function __construct()
    {
    }

    public static function with_args_constructor(
        int $user_id,
        ResourceType       $resource_type,
        string            $description,
        ActionStatus      $action_status,
        DateTimeImmutable $timestamp,
        ?int               $resource_id = null
    ): self
    {
        $instance = new self();

        $instance->resource_id = $resource_id;
        $instance->resource_type = $resource_type;
        $instance->description = $description;
        $instance->action_status = $action_status;
        $instance->timestamp = $timestamp;
        $instance->user_id = $user_id;

        return $instance;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['resource_id'])) {
            $data['resource_id'] = null;
        }

        return self::with_args_constructor(
            $data['user_id'],
            ResourceType::from($data['resource_type']),
            $data['description'],
            ActionStatus::from($data['action_status']),
            $data['timestamp'],
            $data['resource_id']
        );
    }

    public static function builder(): self
    {
        return new LogDTO()->setResourceId()->setActionStatus(ActionStatus::NA);
    }

    public function setResourceId(?int $resource_id = null): LogDTO
    {
        $this->resource_id = $resource_id;
        return $this;
    }

    public function setUserId(int $user_id): LogDTO
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function setResourceType(ResourceType $resource_type): LogDTO
    {
        $this->resource_type = $resource_type;
        return $this;
    }

    public function setDescription(string $description): LogDTO
    {
        $this->description = $description;
        return $this;
    }

    public function setActionStatus(ActionStatus $action_status): LogDTO
    {
        $this->action_status = $action_status;
        return $this;
    }

    public function setTimestamp(DateTimeImmutable $timestamp): LogDTO
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getResourceId(): ?int
    {
        return $this->resource_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getResourceType(): ResourceType
    {
        return $this->resource_type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getActionStatus(): ActionStatus
    {
        return $this->action_status;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

}