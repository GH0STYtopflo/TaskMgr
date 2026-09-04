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

        $data = $data->toArray() + ['status' => TaskStatus::SUBMITTED->value]; // pass on submitted during insertion
        $data['deadline'] = $data['deadline']->format(DATE_ATOM);

        try {
            $res = $this->handle->preparedStatement(
                "INSERT INTO tasks (title, \"desc\", priority, deadline, status)
                    VALUES (:title, :desc, :priority, :deadline, :status)
                    RETURNING *",
                $data
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
        if (!is_null($pri) && ($pri > 20 || $pri < 0)) {
            throw new PriorityOutOfRangeException($pri, line: __LINE__);
        }

        $data = $data->toArray();

        if (!is_null($data['deadline'])) {
            $data['deadline'] = $data['deadline']->format(DATE_ATOM);
        }

        try {
            return $this->handle->preparedStatement(
                "UPDATE tasks SET 
                 title = COALESCE(:title, title),
                 \"desc\" = COALESCE(:desc, \"desc\"),
                 priority = COALESCE(:priority, priority),
                 deadline = COALESCE(:deadline, deadline),
                 updated_at = now()
                 WHERE id = :id 
                 RETURNING *",
                $data
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
        $data = $data->toArray();
        $data['deadline_before'] = $data['deadline_before']?->format(DATE_ATOM);
        $data['deadline_after'] = $data['deadline_after']?->format(DATE_ATOM);
        $data['created_before'] = $data['created_before']?->format(DATE_ATOM);
        $data['created_after'] = $data['created_after']?->format(DATE_ATOM);
        $data['updated_before'] = $data['updated_before']?->format(DATE_ATOM);
        $data['updated_after'] = $data['updated_after']?->format(DATE_ATOM);
        $data['status'] = $data['status']?->value;

        $data['offset'] = ($data['page'] - 1) * $data['limit'];
        unset($data['page']);

        try {
            return $this->handle->preparedStatement(
                "SELECT * FROM tasks WHERE
                    (title ILIKE '%' || :title || '%' OR :title IS NULL) AND 
                    (priority >= :priority_higher OR :priority_higher IS NULL) AND
                    (priority <= :priority_lower OR :priority_lower IS NULL) AND
                    (deadline <= :deadline_before OR :deadline_before IS NULL) AND 
                    (deadline >= :deadline_after OR :deadline_after IS NULL) AND
                    (status = :status OR :status IS NULL) AND 
                    (created_at <= :created_before OR :created_before IS NULL) AND
                    (created_at >= :created_after OR :created_after IS NULL) AND
                    (updated_at <= :updated_before OR :updated_before IS NULL) AND
                    (updated_at >= :updated_after OR :updated_after IS NULL)
                    LIMIT :limit 
                    OFFSET :offset",
                $data
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

            if ($assignee_count > 0) {
                $this->handle->preparedStatement(
                    "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :task_id", ['status' => TaskStatus::ONGOING->value,
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

            if ($assignee_count < 1) {
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
        $data = $data->toArray();
        $data['status'] = $data['status']->value;

        try {
            return $this->handle->preparedStatement(
                "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :id RETURNING *",
                $data
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