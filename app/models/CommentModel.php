<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;

class CommentModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(array $data): void
    {
        $this->handle->preparedStatement(
            "INSERT INTO comments (body, submission_time, user_id, task_id) VALUES (:body, :submission_time, :user_id, :task_id)",
            $data
        );
    }

    public function findById(int $id): ?array
    {
        $result = $this->handle->preparedStatement("SELECT * FROM comments WHERE id = :id", ['id' => $id])->fetch();

        if (!$result) {
            return null;
        } else return $result;
    }

    public function findAll(): array
    {
        return $this->handle->query("SELECT * FROM comments")->fetchAll();
    }

    public function update(float $id, array $data): void
    {
        $this->handle->preparedStatement(
            "UPDATE comments SET 
                    body = COALESCE(:body, body),
                    submission_time = COALESCE(:submission_time, submission_time),
                    user_id = COALESCE(:user_id, user_id),
                    task_id = COALESCE(:task_id, task_id)
                WHERE id = :id",
            $data + ['id' => $id]
        );
    }

    public function delete(int $id): void
    {
        $this->handle->preparedStatement(
            "DELETE FROM comments WHERE id = :id",
            ['id' => $id]
        );
    }

    public function search(array $data): array
    {
        return $this->handle->preparedStatement(
            "SELECT * FROM comments WHERE 
                           (:body IS NULL OR body = :body) AND
                           (:submission_time IS NULL OR submission_time = :submission_time) AND
                           (:user_id IS NULL OR user_id = :user_id) AND
                           (:task_id IS NULL OR task_id = :task_id)",
            $data)->fetchAll();
    }
}