<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\SubtaskExistsException;
use ghosty\taskmgr\logger\Severity;
use PDOException;

class SubTaskModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(DTO $data): void
    {
        try {
            $this->handle->preparedStatement(
                "INSERT INTO sub_tasks (title,task_id) VALUES (:title, :task_id)",
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

    public function findById(DTO $data): ?array
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT * FROM sub_tasks WHERE id = :id",
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
            return $this->handle->query("SELECT * FROM sub_tasks")->fetchAll();
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

    public function update(DTO $data): void
    {
        if ($this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException($data->getId(), 'sub_tasks', line: __LINE__);
        }

        if (!is_null($data->getTitle()) && $this->existsByTitle($data->getTitle())) {
            throw new SubtaskExistsException($data->getTitle(), line: __LINE__);
        }

        try {
            $this->handle->preparedStatement(
                "UPDATE sub_tasks SET 
                     title = COALESCE(:title, title),
                     is_done = COALESCE(:is_done, is_done)",
                $data->toArray());
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

    public function delete(DTO $data): void
    {
        if ($this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException($data->getId(), 'sub_tasks', line: __LINE__);
        }

        try {
            $this->handle->preparedStatement("DELETE FROM sub_tasks WHERE id = :id", $data->toArray());
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

    public function search(DTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT * FROM sub_tasks WHERE 
                            (:title IS NULL OR title LIKE :title) AND
                            (:is_done IS NULL OR is_done = :is_done) AND 
                            (:task_id IS NULL OR task_id = :task_id)",
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

    protected function existsById(int $id): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM sub_tasks WHERE id = :id)",
                ['id' => $id]
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
                "SELECT EXISTS(SELECT 1 FROM sub_tasks WHERE title = :title)",
                ['title' => $title]
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