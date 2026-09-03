<?php

namespace ghosty\taskmgr\dto\user;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class UserDTO extends DTO
{
    private int $id;
    private string $username;

    /**
     * @param int $id
     * @param string $username
     */
    public function __construct(int $id, string $username)
    {
        $this->id = $id;
        $this->username = $username;
    }


    public static function fromArray(array $data): self
    {
        if (!isset($data['id'])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!isset($data['username'])) {
            throw new MissingParamException('username', line: __LINE__);
        }

        if (!is_numeric($data['id'])) {
            throw new TypeMismatchException('id','string('. $data['id'] . ')', 'int' , line: __LINE__);
        }

        return new self((int)$data['id'], $data['username']);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}