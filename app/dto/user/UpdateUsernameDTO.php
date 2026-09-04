<?php

namespace ghosty\taskmgr\dto\user;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class UpdateUsernameDTO extends DTO
{
    private int $id;

    private string $password;
    private string $new_username;

    public function __construct(int $id, string $new_username, string $password)
    {
        $this->id = $id;
        $this->new_username = $new_username;
        $this->password = $password;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data["id"])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!isset($data["new_username"])) {
            throw new MissingParamException('new_username', line: __LINE__);
        }

        if (!isset($data["password"])) {
            throw new MissingParamException('password', line: __LINE__);
        }

        if (!is_numeric($data["id"])) {
            throw new TypeMismatchException(
                'id',
                'string(' . $data["id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        return new self($data["id"], $data["new_username"], $data["password"]);
    }

    public function toArray(): array
    {
        $arr = ['password_hash', 'is_admin'];

        return parent::toArray() + array_fill_keys($arr, null);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNewUsername(): string
    {
        return $this->new_username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}