<?php

namespace ghosty\taskmgr\dto\log;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use ghosty\taskmgr\database\custom_types\ActionStatus;
use ghosty\taskmgr\database\custom_types\ResourceType;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MalformedDateException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class SearchLogDTO extends DTO
{
    private ?ResourceType $resource_type;
    private ?int $user_id;
    private ?int $resource_id;
    private ?ActionStatus $action_status;
    private ?DateTimeImmutable $before;
    private ?DateTimeImmutable $after;

    public function __construct(?ResourceType $resource_type, ?int $user_id, ?int $resource_id, ?ActionStatus $action_status, ?DateTimeImmutable $before, ?DateTimeImmutable $after)
    {
        $this->resource_type = $resource_type;
        $this->user_id = $user_id;
        $this->resource_id = $resource_id;
        $this->action_status = $action_status;
        $this->before = $before;
        $this->after = $after;
    }

    public static function fromArray(array $data): DTO
    {
        if (isset($data['resource_type'])) {
            try {
                $resourceType = ResourceType::from(($data['resource_type']));
            } catch (\ValueError $e) {
                throw new TypeMismatchException('resource', $data['resource_type'], 'Resource type', $e, __LINE__);
            }
        } else {
            $resourceType = null;
        }

        if (isset($data['user_id'])) {
            if (!is_numeric($data['user_id'])) {
                throw new TypeMismatchException('user_id', $data['user_id'], 'int', line: __LINE__);
            }
        } else {
            $userId = null;
        }

        if (isset($data['resource_id'])) {
            if (!is_numeric($data['resource_id'])) {
                throw new TypeMismatchException('resource_id', $data['resource_id'], 'int', line: __LINE__);
            }
        } else {
            $resourceId = null;
        }

        if (isset($data['action_status'])) {
            try {
                $actionStatus = ActionStatus::from(($data['action_status']));
            } catch (\ValueError $e) {
                throw new TypeMismatchException('action', $data['action_status'], 'Action status', $e, __LINE__);
            }
        } else {
            $actionStatus = null;
        }

        if (isset($data['before'])) {
            try {
                $before = new DateTimeImmutable($data['before'], new DateTimeZone('Asia/Tehran'));
            } catch (\DateMalformedStringException) {
                throw new MalformedDateException($data['before']);
            }
        } else {
            $before = null;
        }

        if (isset($data['after'])) {
            try {
                $after = new DateTimeImmutable($data['after'], new DateTimeZone('Asia/Tehran'));
            } catch (DateMalformedStringException) {
                throw new MalformedDateException($data['after']);
            }
        } else {
            $after = null;
        }

        return new self($resourceType, $userId, $resourceId, $actionStatus, $before, $after);
    }

    public function getResourceType(): ?ResourceType
    {
        return $this->resource_type;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getResourceId(): ?int
    {
        return $this->resource_id;
    }

    public function getActionStatus(): ?ActionStatus
    {
        return $this->action_status;
    }

    public function getBefore(): ?DateTimeImmutable
    {
        return $this->before;
    }

    public function getAfter(): ?DateTimeImmutable
    {
        return $this->after;
    }
}