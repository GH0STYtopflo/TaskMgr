<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\category\CreateAndSearchCategoryDTO;
use ghosty\taskmgr\dto\category\FindCategoryByIdDTO;
use ghosty\taskmgr\dto\category\UpdateCategoryDTO;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\dto\task\FindTaskByIdDTO;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\logger\Severity;
use PDOException;

class CategoryModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(DTO|CreateAndSearchCategoryDTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "INSERT INTO categories (title) VALUES (:title) RETURNING *",
                $data->toArray()
            )->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                500,
                Severity::WARNING,
                $e,
                __LINE__
            );
        }
    }

    public function findById(DTO|FindCategoryByIdDTO $data): ?array
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT * FROM categories WHERE id = :id",
                $data->toArray()
            )->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                500,
                Severity::WARNING,
                $e,
                __LINE__
            );
        }
    }

    public function findAll(): array
    {
        try {
            return $this->handle->query("SELECT * FROM categories")->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                500,
                Severity::WARNING,
                $e,
                __LINE__
            );
        }
    }

    public function update(DTO|UpdateCategoryDTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "UPDATE categories SET title = COALESCE(:new_title, title) WHERE id = :id RETURNING *",
                $data->toArray()
            )->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                500,
                Severity::WARNING,
                $e,
                line: __LINE__
            );
        }
    }

    public function delete(DTO|FindCategoryByIdDTO $data): void
    {
        try {
            $this->handle->preparedStatement(
                "DELETE FROM categories WHERE id = :id",
                $data->toArray()
            );
        } catch (PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                500,
                Severity::WARNING,
                $e,
                __LINE__
            );
        }
    }

    public function search(DTO|CreateAndSearchCategoryDTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT * FROM categories WHERE :title IS NULL OR title LIKE :title",
                $data->toArray()
            )->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                500,
                Severity::WARNING,
                $e,
                __LINE__
            );
        }
    }

    public function existsById(int $id): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM categories WHERE id = :id)",
                ["id" => $id]
            )->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                500,
                Severity::WARNING,
                $e,
                __LINE__
            );
        }
    }

    public function existsByTitle(string $title): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM categories WHERE title = :title)",
                ["title" => $title]
            )->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException(
                $e->getMessage(),
                500,
                Severity::WARNING,
                $e,
                __LINE__
            );
        }
    }

    public function getTaskCategories(FindTaskByIdDTO $dto): array
    {
        return $this->handle->preparedStatement(
            "SELECT categories.*, task_id FROM categories JOIN task_categories ON 
                        categories.id = task_categories.category_id WHERE task_id = :task_id",
            ["task_id" => $dto->getId()]
        )->fetchAll();
    }
}