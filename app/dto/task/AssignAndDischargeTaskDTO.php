<?php

namespace ghosty\taskmgr\dto\task;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

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


    public static function fromArray(array $data): self
    {
        if (!isset($data['user_id'])) {
            throw new MissingParamException('user_id', line: __LINE__);
        }

        if (!isset($data['task_id'])) {
            throw new MissingParamException('task_id', line: __LINE__);
        }

        if (!is_numeric($data['user_id'])) {
            throw new TypeMismatchException('user_id', "string(" . $data['user_id'] . ")", 'int'  ,line: __LINE__);
        }

        if (!is_numeric($data['task_id'])) {
            throw new TypeMismatchException('task_id', "string(" . $data['task_id'] . ")", 'int'  ,line: __LINE__);
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