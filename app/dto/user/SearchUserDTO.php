<?php

namespace ghosty\taskmgr\dto\user;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;
use ghosty\taskmgr\exceptions\WrongPaginationParamsException;

class SearchUserDTO extends DTO
{
    private string $username;
    private bool $is_admin;

    private int $page;
    private int $limit;

    public function __construct(string $username, bool $is_admin, int $page, int $limit)
    {
        $this->username = $username;
        $this->is_admin = $is_admin;
        $this->page = $page;
        $this->limit = $limit;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['username'])) {
            throw new MissingParamException('username', line: __LINE__);
        }

        if (!isset($data['is_admin'])) {
            throw new MissingParamException('is_admin', line: __LINE__);
        }

        if (!is_bool($data['is_admin'])) {
            throw new TypeMismatchException('is_admin', $data['is_admin'], 'bool', line: __LINE__);
        }

        if (isset($data['page']) && $data['page'] < 1) {
            throw new WrongPaginationParamsException(line: __LINE__);
        }

        if (isset($data['limit']) && $data['limit'] < 1) {
            throw new WrongPaginationParamsException(line: __LINE__);
        }

        $page = $data['page'] ?? 1;
        $limit = $data['limit'] ?? 10;


        return new self($data['username'], $data['is_admin'], $data['page'], $data['limit']);
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function isIsAdmin(): bool
    {
        return $this->is_admin;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}