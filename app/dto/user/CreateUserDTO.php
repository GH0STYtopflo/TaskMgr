<?php

namespace ghosty\taskmgr\dto\user;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;

class CreateUserDTO extends DTO
{
    private string $username;
    private string $password;

    /**
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }


    public static function fromArray(array $data): self
    {
        if (!isset($data['username'])) {
            throw new MissingParamException('username', line: __LINE__);
        }

        if (!isset($data['password'])) {
            throw new MissingParamException('password', line: __LINE__);
        }

        return new self($data['username'], $data['password']);
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}