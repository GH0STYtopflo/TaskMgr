<?php

namespace ghosty\taskmgr\dto\task;

use ghosty\taskmgr\dto\DTO;

class CategoryAdditionResponseDTO extends DTO
{
    private int $task_id;
    private int $category_id;

    /**
     * @param int $task_id
     * @param int $category_id
     */
    public function __construct(int $task_id, int $category_id)
    {
        $this->task_id = $task_id;
        $this->category_id = $category_id;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['task_id'], $data['category_id']);
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }

    public function getCategoryId(): int
    {
        return $this->category_id;
    }
}