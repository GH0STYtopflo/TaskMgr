<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\database\DBHandle;

class TaskModel extends Model
{

    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(array $data): int
    {
        $res = $this->handle->preparedStatement(
            "INSERT INTO tasks (title, \"desc\", priority, deadline)
                    VALUES (:title, :desc, :priority, :deadline, :status)
                    RETURNING id",
            $data + ['status' => TaskStatus::SUBMITTED] // pass on submitted during insertion
        );

        return $res->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        return $this->handle->preparedStatement("SELECT * FROM tasks WHERE id = :id", ["id" => $id])->fetch();
    }

    public function findAll(): array
    {
        return $this->handle->query("SELECT * FROM tasks")->fetchAll();
    }

    public function update(float $id, array $data): void
    {
        $this->handle->preparedStatement(
            "UPDATE tasks SET 
                 title = COALESCE(:title, title),
                 \"desc\" = COALESCE(:desc, \"desc\"),
                 priority = COALESCE(:priority, priority),
                 deadline = COALESCE(:deadline, deadline),
                 updated_at = now()",
            $data + ['id' => $id]
        );
    }

    public function delete(int $id): void
    {
        $this->handle->preparedStatement("DELETE FROM tasks WHERE id = :id", ["id" => $id]);
    }

    public function search(array $data): array
    {
        return $this->handle->preparedStatement(
            "SELECT * FROM tasks WHERE 
                        (:title IS NULL OR title LIKE :title) AND
                        (:priority IS NULL OR priority = :priority) AND
                        (:deadline IS NULL OR deadline = :deadline) AND
                        (:status IS NULL OR status = :status) AND
                        (:created_at IS NULL OR created_at = :created_at)",
            $data
        )->fetchAll();
    }

    public function assignTaskToUser(array $data): void
    {
        $this->handle->preparedStatement(
            "INSERT INTO user_tasks (user_id, task_id) VALUES (:user_id, :task_id)",
            $data
        );

        $this->handle->preparedStatement(
            "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :task_id", ['status' => TaskStatus::ONGOING,
            "task_id" => $data["task_id"]]);
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
            // TODO: THROW EXCEPTION
        }
        $this->handle->preparedStatement(
            "UPDATE tasks SET status = :status, updated_at = now() WHERE id = :id",
            $data
        );
    }


}