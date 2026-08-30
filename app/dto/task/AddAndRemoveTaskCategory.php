<?php

namespace ghosty\taskmgr\dto\task;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class AddAndRemoveTaskCategory extends DTO
{
    private int $category_id;
    private int $task_id;

    public function __construct(int $category_id, int $task_id)
    {
        $this->category_id = $category_id;
        $this->task_id = $task_id;
    }

    public static function fromArray(array $data): DTO
    {
        if (!isset($data["task_id"])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!isset($data["category_id"])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!is_numeric($data["task_id"])) {
            throw new TypeMismatchException(
                'id',
                'string(' . $data["id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        if (!is_numeric($data["category_id"])) {
            throw new TypeMismatchException(
                'id',
                'string(' . $data["id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        return new self($data["category_id"], $data["task_id"]);
    }

    public function getCategoryId(): int
    {
        return $this->category_id;
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }
}