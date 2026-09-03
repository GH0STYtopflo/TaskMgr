<?php

namespace ghosty\taskmgr\dto\task;

use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\dto\DTO;

class TaskAssignmentResponseDTO extends DTO
{
    private int $user_id;
    private int $task_id;
    private TaskStatus $status;

    public function __construct(int $user_id, int $task_id, TaskStatus $status)
    {
        $this->user_id = $user_id;
        $this->task_id = $task_id;
        $this->status = $status;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['user_id'], $data['task_id'], TaskStatus::from($data['status']));
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }
}