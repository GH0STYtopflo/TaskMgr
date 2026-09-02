<?php

namespace ghosty\taskmgr\dto;

class AuthorizationContext extends DTO
{
    private int $id;
    private bool $isAdmin;

    /**
     * @param int $id
     * @param bool $isAdmin
     */
    public function __construct(int $id, bool $isAdmin)
    {
        $this->id = $id;
        $this->isAdmin = $isAdmin;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['id'], $data['is_admin']);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }
}