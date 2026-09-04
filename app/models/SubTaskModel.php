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
        if (($data instanceof UpdateSubtaskTitleDTO) && $this->existsByTitleForTask($data->getNewTitle(), $data->getId())) {
            throw new SubtaskExistsException($data->getNewTitle(), line: __LINE__);
        }

        try {
            $data_array = $data->toArray();

            if (isset($data_array['is_done'])) {
                $data_array['is_done'] = $data_array['is_done'] ? 'true' : 'false';
            }

            return $this->handle->preparedStatement(
                "UPDATE sub_tasks SET 
             title = COALESCE(:new_title, title),
             is_done = COALESCE(CAST(:is_done AS BOOLEAN), is_done)
             WHERE id = :id
             RETURNING *",
                $data_array)->fetch();
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
                            (title ILIKE '%' || :title || '%' OR :title IS NULL) AND 
                            (is_done = :is_done OR :is_done IS NULL) AND
                            (task_id = :task_id OR :task_id IS NULL);
",
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

    public function taskHasActiveSubtask(int $taskId): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS (SELECT 1 FROM sub_tasks WHERE task_id = :task_id AND is_done = FALSE)",
                ['task_id' => $taskId]
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