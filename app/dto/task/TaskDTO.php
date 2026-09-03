<?php

namespace ghosty\taskmgr\dto\task;

use DateTimeImmutable;
use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\dto\DTO;

class TaskDTO extends DTO
{
    private int $id;
    private string $title;
    private string $desc;
    private TaskStatus $status;
    private int $priority;
    private DateTimeImmutable $deadline;
    private DateTimeImmutable $created_at;
    private DateTimeImmutable $updated_at;

    /**
     * @param int $id
     * @param string $title
     * @param string $desc
     * @param TaskStatus $status
     * @param int $priority
     * @param DateTimeImmutable $deadline
     * @param DateTimeImmutable $created_at
     * @param DateTimeImmutable $updated_at
     */
    public function __construct(int $id, string $title, string $desc, TaskStatus $status, int $priority, DateTimeImmutable $deadline, DateTimeImmutable $created_at, DateTimeImmutable $updated_at)
    {
        $this->id = $id;
        $this->title = $title;
        $this->desc = $desc;
        $this->status = $status;
        $this->priority = $priority;
        $this->deadline = $deadline;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }


    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            $data['title'],
            $data['desc'],
            TaskStatus::from($data['status']),
            (int) $data['priority'],
            new DateTimeImmutable($data['deadline']),
            new DateTimeImmutable($data['created_at']),
            new DateTimeImmutable($data['updated_at'])
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDesc(): string
    {
        return $this->desc;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getDeadline(): DateTimeImmutable
    {
        return $this->deadline;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
    }
}