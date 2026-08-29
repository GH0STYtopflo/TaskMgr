<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;

class CategoryModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(array $data): void
    {
        $this->handle->preparedStatement(
            "INSERT INTO categories (title) VALUES (:title)",
            $data
        );
    }

    public function findById(int $id): ?array
    {
        return $this->handle->preparedStatement(
            "SELECT * FROM categories WHERE id = :id",
            ["id" => $id]
        )->fetch();
    }

    public function findAll(): array
    {
        return $this->handle->query("SELECT * FROM categories")->fetchAll();
    }

    public function update(float $id, array $data): void
    {
        $this->handle->preparedStatement(
            "UPDATE categories SET title = COALESCE(:title, title) WHERE id = :id",
            $data + ['id' => $id]
        );
    }

    public function delete(int $id): void
    {
        $this->handle->preparedStatement(
            "DELETE FROM categories WHERE id = :id",
            ["id" => $id]
        );
    }

    public function search(array $data): array
    {
        return $this->handle->preparedStatement(
            "SELECT * FROM categories WHERE :title IS NULL OR title LIKE :title",
            $data
        )->fetchAll();
    }
}