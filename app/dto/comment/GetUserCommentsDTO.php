<?php

namespace ghosty\taskmgr\dto\comment;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class GetUserCommentsDTO extends DTO
{
    private $user_id;

    /**
     * @param $user_id
     */
    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data["user_id"])) {
            throw new MissingParamException('user_id', line: __LINE__);
        }

        if (!is_numeric($data["user_id"])) {
            throw new TypeMismatchException(
                'user_id',
                'string(' . $data["user_id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        return new self($data["user_id"]);
    }

    public function toArray(): array
    {
        return parent::toArray() + ['task_id' => null];
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }
}