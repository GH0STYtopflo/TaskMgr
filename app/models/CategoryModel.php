<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\category\CreateAndSearchCategoryDTO;
use ghosty\taskmgr\dto\category\FindCategoryById;
use ghosty\taskmgr\dto\category\UpdateCategoryDTO;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\CategoryExistsException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\logger\Severity;
use PDOException;

class CategoryModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(DTO | CreateAndSearchCategoryDTO $data): void
    {
        if ($this->existsById($data->getTitle())) {
            throw new CategoryExistsException(
                $data->getTitle(),
                line: __LINE__,
            );
        }

        try {
            $this->handle->preparedStatement(
                "INSERT INTO categories (title) VALUES (:title)",
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

    public function findById(DTO | FindCategoryById $data): ?array
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

    public function update(DTO | UpdateCategoryDTO $data): void
    {
        if (!$this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException(
                $data->getId(),
                'categories',
                line: __LINE__,
            );
        }

        if ($this->existsByTitle($data->getTitle())) {
            throw new CategoryExistsException(
                $data->getTitle(),
                line: __LINE__
            );
        }

        try {
            $this->handle->preparedStatement(
                "UPDATE categories SET title = COALESCE(:new_title, title) WHERE id = :id",
                $data->toArray()
            );
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

    public function delete(DTO | FindCategoryById $data): void
    {
        if (!$this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException(
                $data->getId(),
                'categories',
                line: __LINE__
            );
        }

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

    public function search(DTO | CreateAndSearchCategoryDTO $data): array
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

    protected function existsByTitle(string $title): bool
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
}