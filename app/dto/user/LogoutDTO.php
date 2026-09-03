<?php

namespace ghosty\taskmgr\dto\user;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;

class LogoutDTO extends DTO
{
    private string $token;

    /**
     * @param string $token
     */
    public function __construct(string $token)
    {
        $segments = explode('.', $token);
        $this->token = $segments[1] . $segments[2]; // trying to reduce the pressure on db
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['token'])) {
            throw new MissingParamException('token', line: __LINE__);
        }

        return new self($data['token']);
    }

    public function getToken(): string
    {
        return $this->token;
    }
}