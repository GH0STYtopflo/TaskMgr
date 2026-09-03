<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\dto\task\AddAndRemoveTaskCategory;
use ghosty\taskmgr\dto\task\AssignAndDischargeTaskDTO;
use ghosty\taskmgr\dto\task\CreateTaskDTO;
use ghosty\taskmgr\dto\task\FindTaskByIdDTO;
use ghosty\taskmgr\dto\task\UpdateTaskStatusDTO;
use ghosty\taskmgr\dto\user\FindUserByIdDTO;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\PriorityOutOfRangeException;
use ghosty\taskmgr\logger\Severity;
use PDOException;

class TaskModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(CreateTaskDTO | DTO $data): array
    {
        $pri = $data->getPriority();
        if ($pri > 20 || $pri < 0) {
            throw new PriorityOutOfRangeException($pri, line: __LINE__);
        }

        try {
            $res = $this->handle->preparedStatement(
                "INSERT INTO tasks (title, \"desc\", priority, deadline, status)
                    VALUES (:title, :desc, :priority, :deadline, :status)
                    RETURNING *",
                $data->toArray() + ['status' => TaskStatus::SUBMITTED] // pass on submitted during insertion
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }

        return $res->fetch();
    }

    public function findById(DTO | FindTaskByIdDTO $data): ?array
    {
        try {
            $task = $this->handle->preparedStatement("SELECT * FROM tasks WHERE id = :id", $data->toArray())->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }

        if (empty($task)) {
            return null;
        }

        return $task;
    }

    public function findAll(): array
    {
        try {
            return $this->handle->query("SELECT * FROM tasks")->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function update(DTO | UpdateTaskStatusDTO $data): array
    {
        $pri = $data->getPriority();
        if ($pri > 20 || $pri < 0) {
            throw new PriorityOutOfRangeException($pri, line: __LINE__);
        }

        try {
            return $this->handle->preparedStatement(
                "UPDATE tasks SET 
                 title = COALESCE(:title, title),
                 \"desc\" = COALESCE(:desc, \"desc\"),
                 priority = COALESCE(:priority, priority),
                 deadline = COALESCE(:deadline, deadline),
                 updated_at = now()
                 RETURNING *",
                $data->toArray()
            )->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function delete(DTO $data): void
    {
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

    public function assignTaskToUser(DTO | AssignAndDischargeTaskDTO $data): array
    {
        try {
            $this->handle->preparedStatement(
                "INSERT INTO user_tasks (user_id, task_id) VALUES (:user_id, :task_id)",
                $data->toArray()
            );

            $assignee_count = $this->handle->preparedStatement(
                "SELECT COUNT(*) FROM user_tasks WHERE task_id = :task_id",
                ['task_id' => $data->getTaskId()]
            )->fetchColumn();

            if ($assignee_count == 1) {
                $this->handle->preparedStatement(
                    "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :task_id", ['status' => TaskStatus::ONGOING,
                    "task_id" => $data->getTaskId()]);
            }

            return $this->handle->preparedStatement(
                "SELECT user_tasks.user_id, tasks.id, tasks.status FROM tasks JOIN user_tasks ON tasks.id = user_tasks.task_id WHERE user_tasks.user_id = :user_id AND tasks.id = :task_id",
                ['task_id' => $data->getTaskId(), 'user_id' => $data->getUserId()]
            )->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function dischargeUserFromTask(DTO | AssignAndDischargeTaskDTO $data): void
    {
        try {
            $this->handle->preparedStatement(
                "DELETE FROM user_tasks WHERE user_id = :user_id AND task_id = :task_id",
                $data->toArray()
            );

            $assignee_count = $this->handle->preparedStatement(
                "SELECT COUNT(*) FROM user_tasks WHERE task_id = :task_id",
                ['task_id' => $data->getTaskId()]
            )->fetchColumn();

            if ($assignee_count == 0) {
                $this->handle->preparedStatement(
                    "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :task_id",
                    ['status' => TaskStatus::SUBMITTED, "task_id" => $data->getTaskId()]
                );
            }
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function updateTaskStatus(UpdateTaskStatusDTO | DTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :id RETURNING *",
                $data->toArray()
            )->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function getUserTasks(FindUserByIdDTO | DTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT tasks.* FROM tasks JOIN user_tasks ON tasks.id = user_tasks.task_id WHERE public.user_tasks.user_id = :id",
                $data->toArray()
            )->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function addTaskCategory(DTO | AddAndRemoveTaskCategory $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "INSERT INTO task_categories (task_id, category_id) VALUES (:task_id, :category_id) RETURNING *",
                $data->toArray()
            )->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function removeTaskCategory(DTO | AddAndRemoveTaskCategory $data): void
    {
        try {
            $this->handle->preparedStatement(
                "DELETE FROM task_categories WHERE task_id = :task_id AND category_id = :category_id",
                $data->toArray()
            );
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

    public function assignmentExists(DTO $data): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM user_tasks WHERE task_id = :task_id AND user_id = :user_id)",
                ['task_id' => $data->getTaskId(), 'user_id' => $data->getUserId()]
            )->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function taskCategoryExists(DTO $data): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM task_categories WHERE task_id = :task_id AND category_id = :category_id)",
                ["task_id" => $data->getTaskId(), "category_id" => $data->getCategoryId()]
            )->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function isUserAssignedToTask(int $userId, int $taskId): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM user_tasks WHERE task_id = :task_id AND user_id = :user_id)",
                ["task_id" => $taskId, "user_id" => $userId]
            )->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }
}