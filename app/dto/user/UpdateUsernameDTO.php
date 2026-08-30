<?php

namespace ghosty\taskmgr\dto\user;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class UpdateUsernameDTO extends DTO
{
    private int $id;
    private string $new_username;

    /**
     * @param int $id
     * @param string $new_username
     */
    public function __construct(int $id, string $new_username)
    {
        $this->id = $id;
        $this->new_username = $new_username;
    }

    public static function fromArray(array $data): DTO
    {
        if (!isset($data["id"])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!isset($data["new_username"])) {
            throw new MissingParamException('new_username', line: __LINE__);
        }

        if (!is_numeric($data["id"])) {
            throw new TypeMismatchException(
                'id',
                'string(' . $data["id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        return new self($data["id"], $data["new_username"]);
    }
}