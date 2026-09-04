<?php

namespace ghosty\taskmgr\dto\subtask;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class SearchSubtaskDTO extends DTO
{
    private ?string $title;
    private ?bool $is_done;

    public function __construct(?string $title, ?bool $is_done)
    {
        $this->title = $title;
        $this->is_done = $is_done;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['title'])) {
            $data['title'] = null;
        }

        if (!isset($data['is_done'])) {
            $data['is_done'] = null;
        }

        if (isset($data['is_done']) && !is_bool($data["is_done"])) {
            throw new TypeMismatchException('is_done', $data['is_done'], 'bool', line: __LINE__);
        }

        return new self($data["title"], $data["is_done"]);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['task_id'] = null;

        return $array;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function isIsDone(): bool
    {
        return $this->is_done;
    }
}