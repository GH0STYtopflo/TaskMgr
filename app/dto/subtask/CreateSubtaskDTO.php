<?php

namespace ghosty\taskmgr\dto\subtask;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class CreateSubtaskDTO extends DTO
{
    private string $title;
    private int $task_id;

    /**
     * @param string $title
     * @param int $task_id
     */
    public function __construct(string $title, int $task_id)
    {
        $this->title = $title;
        $this->task_id = $task_id;
    }

    public static function fromArray(array $data): DTO
    {
        if (!isset($data["task_id"])) {
            throw new MissingParamException('task_id', line: __LINE__);
        }

        if (!isset($data["title"])) {
            throw new MissingParamException('title', line: __LINE__);
        }

        if (!is_numeric($data["task_id"])) {
            throw new TypeMismatchException(
                'task_id',
                'string(' . $data["task_id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        return new self($data['title'], (int) $data["task_id"]);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }
}