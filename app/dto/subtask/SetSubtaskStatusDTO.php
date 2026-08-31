<?php

namespace ghosty\taskmgr\dto\subtask;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class SetSubtaskStatusDTO extends DTO
{
    private int $id;
    private bool $is_done;

    /**
     * @param int $id
     * @param bool $is_done
     */
    public function __construct(int $id, bool $is_done)
    {
        $this->id = $id;
        $this->is_done = $is_done;
    }


    public static function fromArray(array $data): DTO
    {
        if (!isset($data["id"])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!isset($data["is_done"])) {
            throw new MissingParamException('new_title', line: __LINE__);
        }

        if (!is_numeric($data["id"])) {
            throw new TypeMismatchException(
                'id',
                'string(' . $data["id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        if (!is_bool($data["is_done"])) {
            throw new TypeMismatchException('is_done', $data['is_done'], 'bool', line: __LINE__);
        }

        return new self($data["id"], $data["is_done"]);
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['title'] = null;

        return $array;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isIsDone(): bool
    {
        return $this->is_done;
    }
}