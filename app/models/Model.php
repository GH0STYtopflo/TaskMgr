<?php

namespace ghosty\taskmgr\models;


use ghosty\taskmgr\database\DBHandle;

abstract class Model
{
    protected DBHandle $handle;

    protected function __construct(DBHandle $handle)
    {
        $this->handle = $handle;
    }

    abstract protected function insert(array $data);

    abstract protected function findById(int $id): ?array;

    abstract protected function findAll(): array;

    abstract protected function update(float $id, array $data): void;

    abstract protected function delete(int $id): void;

    abstract protected function search(array $data): array;
}