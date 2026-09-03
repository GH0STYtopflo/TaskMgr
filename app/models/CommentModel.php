<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\comment\CreateCommentDTO;
use ghosty\taskmgr\dto\comment\FindCommentByIdDTO;
use ghosty\taskmgr\dto\comment\GetTaskCommentsDTO;
use ghosty\taskmgr\dto\comment\GetUserCommentsDTO;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\logger\Severity;
use PDOException;

class CommentModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(DTO | CreateCommentDTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "INSERT INTO comments (body, submission_time, user_id, task_id) VALUES (:body, now(), :user_id, :task_id) RETURNING *",
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

    public function findById(DTO | FindCommentByIdDTO $data): ?array
    {
        try {
            $result = $this->handle->preparedStatement(
                "SELECT * FROM comments WHERE id = :id",
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

        if (!$result) {
            return null;
        } else return $result;
    }

    public function findAll(): array
    {
        try {
            return $this->handle->query("SELECT * FROM comments")->fetchAll();
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

    public function update(DTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "UPDATE comments SET 
                    body = COALESCE(:new_body, body)
                WHERE id = :id
                RETURNING *",
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

    public function delete(DTO | FindCommentByIdDTO $data): void
    {
        try {
            $this->handle->preparedStatement(
                "DELETE FROM comments WHERE id = :id",
                $data->toArray()
            );
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

    public function search(DTO | GetUserCommentsDTO | GetTaskCommentsDTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT * FROM comments WHERE
                           (:user_id IS NULL OR user_id = :user_id) AND
                           (:task_id IS NULL OR task_id = :task_id)",
                $data->toArray())->fetchAll();
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
                "SELECT EXISTS(SELECT 1 FROM comments WHERE id = :id)",
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

    public function isAuthor(int $userId, int $commentId): int
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM comments WHERE id = :id AND user_id = :user_id)",
                ['id' => $commentId, 'user_id' => $userId]
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