<?php

namespace ghosty\taskmgr\dto\comment;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class FindTaskComments extends DTO
{
    private int $task_id;

    /**
     * @param int $task_id
     */
    public function __construct(int $task_id)
    {
        $this->task_id = $task_id;
    }


    public static function fromArray(array $data): DTO
    {
        if (!isset($data["task_id"])) {
            throw new MissingParamException('task_id', line: __LINE__);
        }

        if (!is_numeric($data["task_id"])) {
            throw new TypeMismatchException(
                'task_id',
                'string(' . $data["task_id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        return new self((int) $data["task_id"]);
    }

    public function toArray(): array
    {
        return parent::toArray() + ['user_id' => null];
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }
}