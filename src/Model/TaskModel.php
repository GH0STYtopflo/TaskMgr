<?php

namespace Gh0stytopflo\Taskmgr\Model;

use Gh0stytopflo\Taskmgr\Database\DBHandle;

class TaskModel extends Model
{

    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(array $data): void
    {
        $this->handle->preparedStatement(
            "INSERT INTO tasks (title, \"desc\", priority, deadline, created_at, status) 
                    VALUES (:title, :desc, :priority, :deadline, :created_at, :status)",
            $data
        );
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
                 updated_at = COALESCE(:updated_at, updated_at),
                 status = COALESCE(:status, status)",
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
}