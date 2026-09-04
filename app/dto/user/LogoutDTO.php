<?php

namespace ghosty\taskmgr\dto\user;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;

class LogoutDTO extends DTO
{
    private string $token;
    private string $password;

    public function __construct(string $token, string $password)
    {
        $segments = explode('.', $token);
        $this->token = $segments[1] . $segments[2]; // trying to reduce the pressure on db
        $this->password = $password;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['token'])) {
            throw new MissingParamException('token', line: __LINE__);
        }

        if (!isset($data['password'])) {
            throw new MissingParamException('password', line: __LINE__);
        }

        return new self($data['token'], $data['password']);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}