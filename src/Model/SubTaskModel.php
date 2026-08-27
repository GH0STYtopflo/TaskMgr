<?php

namespace Gh0stytopflo\Taskmgr\Model;

use Gh0stytopflo\Taskmgr\Database\DBHandle;

class SubTaskModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(array $data): void
    {
        $this->handle->preparedStatement(
            "INSERT INTO sub_tasks (title, is_done, task_id) VALUES (:title, :is_done, :task_id)",
            $data
        );
    }

    public function findById(int $id): ?array
    {
        return $this->handle->preparedStatement(
            "SELECT * FROM sub_tasks WHERE id = :id",
            ['id' => $id]
        )->fetch();
    }

    public function findAll(): array
    {
        return $this->handle->query("SELECT * FROM sub_tasks")->fetchAll();
    }

    public function update(float $id, array $data): void
    {
        $this->handle->preparedStatement(
            "UPDATE sub_tasks SET 
                     title = COALESCE(:title, title),
                     is_done = COALESCE(:is_done, is_done),
                     task_id = COALESCE(:task_id, task_id)",
            $data + ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->handle->preparedStatement("DELETE FROM sub_tasks WHERE id = :id", ['id' => $id]);
    }

    public function search(array $data): array
    {
        return $this->handle->preparedStatement(
            "SELECT * FROM sub_tasks WHERE 
                            (:title IS NULL OR title LIKE :title) AND
                            (:is_done IS NULL OR task_id = :task_id) AND 
                            (:task_id IS NULL OR task_id = :task_id)",
            $data
        )->fetchAll();
    }
}