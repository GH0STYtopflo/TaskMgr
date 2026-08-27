<?php

namespace Gh0stytopflo\Taskmgr\Model;


use Gh0stytopflo\Taskmgr\Database\DBHandle;

abstract class Model
{
    protected DBHandle $handle;

    protected function __construct(DBHandle $handle)
    {
        $this->handle = $handle;
    }

    abstract protected function insert(array $data): void;

    abstract protected function findById(int $id): ?array;

    abstract protected function findAll(): array;

    abstract protected function update(float $id, array $data): void;

    abstract protected function delete(int $id): void;

    abstract protected function search(array $data): array;
}