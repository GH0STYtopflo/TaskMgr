<?php

namespace ghosty\taskmgr\dto\user;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class UserDTO extends DTO
{
    private int $id;
    private string $username;
    private bool $is_admin;

    /**
     * @param int $id
     * @param string $username
     * @param bool $is_admin
     */
    public function __construct(int $id, string $username, bool $is_admin)
    {
        $this->id = $id;
        $this->username = $username;
        $this->is_admin = $is_admin;
    }


    public static function fromArray(array $data): self
    {
        if (!isset($data['id'])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!isset($data['username'])) {
            throw new MissingParamException('username', line: __LINE__);
        }

        if (!isset($data['is_admin'])) {
            throw new MissingParamException('is_admin', line: __LINE__);
        }

        if (!is_numeric($data['id'])) {
            throw new TypeMismatchException('id','string('. $data['id'] . ')', 'int' , line: __LINE__);
        }

        if (!is_bool($data['is_admin'])) {
            throw new TypeMismatchException('is_admin','string(' . $data['is_admin'] . ')' ,'bool' ,  line: __LINE__);
        }

        return new self($data['id'], $data['username'], $data['is_admin']);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function isIsAdmin(): bool
    {
        return $this->is_admin;
    }
}