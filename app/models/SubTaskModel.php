<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\dto\subtask\CreateSubtaskDTO;
use ghosty\taskmgr\dto\subtask\FindSubtaskById;
use ghosty\taskmgr\dto\subtask\GetTaskSubtask;
use ghosty\taskmgr\dto\subtask\SearchSubtaskDTO;
use ghosty\taskmgr\dto\subtask\SetSubtaskStatusDTO;
use ghosty\taskmgr\dto\subtask\UpdateSubtaskTitleDTO;
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

    public function insert(DTO | CreateSubtaskDTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "INSERT INTO sub_tasks (title,task_id) VALUES (:title, :task_id) RETURNING *",
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

    public function findById(DTO | FindSubtaskById $data): ?array
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

    public function update(DTO | SetSubtaskStatusDTO | UpdateSubtaskTitleDTO $data): array
    {
        if (!is_null($data->getTitle()) && $this->existsByTitleForTask($data->getTitle(), $data->getTaskId())) {
            throw new SubtaskExistsException($data->getTitle(), line: __LINE__);
        }

        try {
            return $this->handle->preparedStatement(
                "UPDATE sub_tasks SET 
                     title = COALESCE(:title, title),
                     is_done = COALESCE(:is_done, is_done)
                     WHERE id = :id
                     RETURNING *",
                $data->toArray())->fetch();
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

    public function delete(DTO | FindSubtaskById $data): void
    {
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

    public function search(DTO | GetTaskSubtask | SearchSubtaskDTO $data): array
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

    public function existsById(int $id): bool
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

    public function existsByTitleForTask(string $title, int $taskId): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM sub_tasks WHERE title = :title AND task_id = :task_id)",
                ['title' => $title, 'task_id' => $taskId]
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

    public function getSubtaskTaskId(int $subTaskId): int
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT sub_tasks.task_id FROM sub_tasks WHERE id = :id",
                ['id' => $subTaskId]
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