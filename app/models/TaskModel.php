<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\dto\task\UpdateTaskDTO;
use ghosty\taskmgr\dto\task\UpdateTaskStatusDTO;
use ghosty\taskmgr\dto\user\FindUserByIdDTO;
use ghosty\taskmgr\dto\user\GetUserTasksDTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\UpdatingTaskStatusToSubmittedException;
use ghosty\taskmgr\logger\Severity;
use PDOException;

class TaskModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(DTO $data): int
    {
        try {
            $res = $this->handle->preparedStatement(
                "INSERT INTO tasks (title, \"desc\", priority, deadline, status)
                    VALUES (:title, :desc, :priority, :deadline, :status)
                    RETURNING id",
                $data->toArray() + ['status' => TaskStatus::SUBMITTED] // pass on submitted during insertion
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }

        return $res->fetchColumn();
    }

    public function findById(DTO $data): ?array
    {
        try {
            return $this->handle->preparedStatement("SELECT * FROM tasks WHERE id = :id", $data->toArray())->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function findAll(): array
    {
        try {
            return $this->handle->query("SELECT * FROM tasks")->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function update(DTO | UpdateTaskDTO $data): void
    {
        $task = $this->findById($data->getId());

        if (is_null($task)) {
            throw new AccessingNonExistentRecordException($data->getId(), 'tasks', line: __LINE__);
        }

        try {
            $this->handle->preparedStatement(
                "UPDATE tasks SET 
                 title = COALESCE(:title, title),
                 \"desc\" = COALESCE(:desc, \"desc\"),
                 priority = COALESCE(:priority, priority),
                 deadline = COALESCE(:deadline, deadline),
                 updated_at = now()",
                $data->getId()
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function delete(DTO $data): void
    {
        if ($this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException($data->getId(), 'tasks', line: __LINE__);
        }

        try {
            $this->handle->preparedStatement("DELETE FROM tasks WHERE id = :id", $data->toArray());
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function search(DTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT * FROM tasks WHERE 
                        (:title IS NULL OR title LIKE :title) AND
                        (:priority IS NULL OR priority = :priority) AND
                        (:deadline IS NULL OR deadline = :deadline) AND
                        (:status IS NULL OR status = :status) AND
                        (:created_at IS NULL OR created_at = :created_at)",
                $data->toArray()
            )->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function assignTaskToUser(DTO $data): void
    {
        try {
            $this->handle->preparedStatement(
                "INSERT INTO user_tasks (user_id, task_id) VALUES (:user_id, :task_id)",
                $data->toArray()
            );

            $this->handle->preparedStatement(
                "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :task_id", ['status' => TaskStatus::ONGOING,
                "task_id" => $data->getTaskId()]);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function dischargeUserFromTask(DTO $data): void
    {
        $this->handle->preparedStatement(
            "DELETE FROM user_tasks WHERE user_id = :user_id AND task_id = :task_id",
            $data->toArray()
        );

        $count = $this->handle->preparedStatement(
            "SELECT COUNT(*) FROM user_tasks WHERE task_id = :task_id",
            ['task_id' => $data->getTaskId()]
        )->fetchColumn();

        if ($count == 0) {
            $this->handle->preparedStatement(
                "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :task_id",
                ['status' => TaskStatus::SUBMITTED, "task_id" => $data->getTaskId()]
            );
        }
    }

    public function updateTaskStatus(UpdateTaskStatusDTO | DTO $data): void
    {
        if ($this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException($data->getId(), 'tasks', line: __LINE__);
        }

        if ($data->getStatus() == TaskStatus::SUBMITTED) {
            throw new UpdatingTaskStatusToSubmittedException(line: __LINE__);
        }

        try {
            $this->handle->preparedStatement(
                "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :id",
                $data->toArray()
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function getUserTasks(FindUserByIdDTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT tasks.* FROM tasks JOIN user_tasks ON tasks.id = user_tasks.task_id WHERE public.user_tasks.user_id = :user_id",
                $data->toArray()
            )->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function existsById(int $id): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM tasks WHERE id = :id)",
                ["id" => $id]
            )->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }
}