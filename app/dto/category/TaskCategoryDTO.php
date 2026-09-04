<?php

namespace ghosty\taskmgr\dto\category;

use ghosty\taskmgr\dto\DTO;

class TaskCategoryDTO extends DTO
{
    private int $task_id;
    private int $category_id;
    private string $title;

    /**
     * @param int $task_id
     * @param int $category_id
     * @param string $title
     */
    public function __construct(int $task_id, int $category_id, string $title)
    {
        $this->task_id = $task_id;
        $this->category_id = $category_id;
        $this->title = $title;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['task_id'], $data['id'], $data['title']);
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }

    public function getCategoryId(): int
    {
        return $this->category_id;
    }

    public function getTitle(): int
    {
        return $this->title;
    }
}