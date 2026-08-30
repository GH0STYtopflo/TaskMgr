<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\user\FindUserByIdDTO;
use ghosty\taskmgr\dto\user\GetUserTasksDTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\UpdatingTaskStatusToSubmittedException;
use ghosty\taskmgr\logger\Severity;
use PDOException;

class TaskModel extends Model
{

    private UserModel $userModel;
    public function __construct(DBHandle $handle, UserModel $userModel)
    {
        parent::__construct($handle);
        $this->userModel = $userModel;
    }

    public function insert(array $data): int
    {
        try {
            $res = $this->handle->preparedStatement(
                "INSERT INTO tasks (title, \"desc\", priority, deadline)
                    VALUES (:title, :desc, :priority, :deadline, :status)
                    RETURNING id",
                $data + ['status' => TaskStatus::SUBMITTED] // pass on submitted during insertion
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }

        return $res->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        try {
            return $this->handle->preparedStatement("SELECT * FROM tasks WHERE id = :id", ["id" => $id])->fetch();
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

    public function update(float $id, array $data): void
    {
        $task = $this->findById($id);

        if (is_null($task)) {
            throw new AccessingNonExistentRecordException($id, 'tasks', line: __LINE__);
        }

        try {
            $this->handle->preparedStatement(
                "UPDATE tasks SET 
                 title = COALESCE(:title, title),
                 \"desc\" = COALESCE(:desc, \"desc\"),
                 priority = COALESCE(:priority, priority),
                 deadline = COALESCE(:deadline, deadline),
                 updated_at = now()",
                $data + ['id' => $id]
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function delete(int $id): void
    {
        $task = $this->findById($id);

        if (is_null($task)) {
            throw new AccessingNonExistentRecordException($id, "tasks", line: __LINE__);
        }

        try {
            $this->handle->preparedStatement("DELETE FROM tasks WHERE id = :id", ["id" => $id]);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function search(array $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT * FROM tasks WHERE 
                        (:title IS NULL OR title LIKE :title) AND
                        (:priority IS NULL OR priority = :priority) AND
                        (:deadline IS NULL OR deadline = :deadline) AND
                        (:status IS NULL OR status = :status) AND
                        (:created_at IS NULL OR created_at = :created_at)",
                $data
            )->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function assignTaskToUser(array $data): void
    {
        $task = $this->findById($data['task_id']);
        $user = $this->userModel->findById($data['user_id']);

        if (is_null($user)) {
            throw new AccessingNonExistentRecordException($data['user_id'], 'users', line: __LINE__);
        }

        if (is_null($task)) {
            throw new AccessingNonExistentRecordException($data['task_id'], 'tasks', line: __LINE__);
        }

        try {
            $this->handle->preparedStatement(
                "INSERT INTO user_tasks (user_id, task_id) VALUES (:user_id, :task_id)",
                $data
            );

            $this->handle->preparedStatement(
                "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :task_id", ['status' => TaskStatus::ONGOING,
                "task_id" => $data["task_id"]]);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function dischargeUserFromTask(array $data): void
    {
        $this->handle->preparedStatement(
            "DELETE FROM user_tasks WHERE user_id = :user_id AND task_id = :task_id",
            $data
        );

        $count = $this->handle->preparedStatement(
            "SELECT COUNT(*) FROM user_tasks WHERE task_id = :task_id",
            ['task_id' => $data["task_id"]]
        );

        if ($count->fetchColumn() == 0) {
            $this->handle->preparedStatement(
                "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :task_id",
                ['status' => TaskStatus::SUBMITTED, "task_id" => $data["task_id"]]
            );
        }
    }

    public function updateTaskStatus(array $data): void
    {
        if ($data["status"] == TaskStatus::SUBMITTED) {
            throw new UpdatingTaskStatusToSubmittedException(line: __LINE__);
        }

        try {
            $this->handle->preparedStatement(
                "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :id",
                $data
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


}