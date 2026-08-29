<?php

namespace ghosty\taskmgr\dto\task;

use ghosty\taskmgr\dto\DTO;

class AssignAndDischargeTaskDTO extends DTO
{
    private int $user_id;
    private int $task_id;

    /**
     * @param int $user_id
     * @param int $task_id
     */
    public function __construct(int $user_id, int $task_id)
    {
        $this->user_id = $user_id;
        $this->task_id = $task_id;
    }


    public static function fromArray(array $data): DTO
    {
        if (!isset($data['user_id'])) {
            // TODO: THROW EXCEPTION
        }

        if (!isset($data['task_id'])) {
            // TODO: THROW EXCEPTION
        }

        return new self((int) $data['user_id'], (int) $data['task_id']);
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }
}