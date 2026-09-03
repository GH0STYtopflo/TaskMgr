<?php

namespace ghosty\taskmgr\dto\user;

use ghosty\taskmgr\dto\DTO;

class LoginResponseDTO extends DTO
{
    private string $token;

    /**
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['token']);
    }
}