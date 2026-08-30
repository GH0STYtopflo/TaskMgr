<?php

namespace ghosty\taskmgr\models;


use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\DTO;

abstract class Model
{
    protected DBHandle $handle;

    protected function __construct(DBHandle $handle)
    {
        $this->handle = $handle;
    }

    abstract protected function insert(DTO $data);

    abstract protected function findById(DTO $data): ?array;

    abstract protected function findAll(): array;

    abstract protected function update(DTO $data): void;

    abstract protected function delete(DTO $data): void;

    abstract protected function search(DTO $data): array;

    abstract protected function existsById(int $id): bool;
}