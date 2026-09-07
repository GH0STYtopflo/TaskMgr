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
    private ActionStatus $result;
    private DateTimeImmutable $timestamp;

    public function __construct(
        ?int               $resource_id,
        int $user_id,
        ResourceType       $resource_type,
        string            $description,
        ActionStatus      $result,
        DateTimeImmutable $timestamp
    )
    {
        $this->resource_id = $resource_id;
        $this->resource_type = $resource_type;
        $this->description = $description;
        $this->result = $result;
        $this->timestamp = $timestamp;
        $this->user_id = $user_id;
    }


    public static function fromArray(array $data): self
    {
        return new self($data['resource_id'], $data['user_id'] ,ResourceType::from($data['resource_type']), $data['description'], ActionStatus::from($data['result']), $data['timestamp']);
    }

    public function getResourceId(): int
    {
        return $this->resource_id;
    }

    public function getResourceType(): ResourceType
    {
        return $this->resource_type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getResult(): ActionStatus
    {
        return $this->result;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }
}