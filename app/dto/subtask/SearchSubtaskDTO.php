<?php

namespace ghosty\taskmgr\dto\subtask;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class SearchSubtaskDTO extends DTO
{
    private string $title;
    private bool $is_done;

    /**
     * @param string $title
     * @param bool $is_done
     */
    public function __construct(string $title, bool $is_done)
    {
        $this->title = $title;
        $this->is_done = $is_done;
    }

    public static function fromArray(array $data): DTO
    {
        if (!isset($data["title"])) {
            throw new MissingParamException('title', line: __LINE__);
        }

        if (!isset($data["is_done"])) {
            throw new MissingParamException('is_done', line: __LINE__);
        }

        if (!is_bool($data["is_done"])) {
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