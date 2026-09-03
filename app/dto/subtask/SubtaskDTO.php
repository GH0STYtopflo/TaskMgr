<?php

namespace ghosty\taskmgr\dto\subtask;

use ghosty\taskmgr\dto\DTO;

class SubtaskDTO extends DTO
{
    private int $id;
    private string $title;
    private bool $is_done;
    private int $task_id;

    /**
     * @param int $id
     * @param string $title
     * @param bool $is_done
     * @param int $task_id
     */
    public function __construct(int $id, string $title, bool $is_done, int $task_id)
    {
        $this->id = $id;
        $this->title = $title;
        $this->is_done = $is_done;
        $this->task_id = $task_id;
    }

    public static function fromArray(array $data): self
    {
        return new self($data["id"], $data["title"], $data["is_done"], $data["task_id"]);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function isIsDone(): bool
    {
        return $this->is_done;
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }
}