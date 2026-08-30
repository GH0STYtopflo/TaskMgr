<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\logger\Severity;
use PDOException;

class CommentModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(DTO $data): void
    {
        try {
            $this->handle->preparedStatement(
                "INSERT INTO comments (body, submission_time, user_id, task_id) VALUES (:body, :submission_time, :user_id, :task_id)",
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

    public function findById(DTO $data): ?array
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

    public function update(DTO $data): void
    {
        if ($this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException(
                $data->getId(),
                'comments',
                line: __LINE__
            );
        }

        try {
            $this->handle->preparedStatement(
                "UPDATE comments SET 
                    body = COALESCE(:body, body)
                WHERE id = :id",
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

    public function delete(DTO $data): void
    {
        if ($this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException($data->getId(), 'comments', line: __LINE__);
        }

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

    public function search(DTO $data): array
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

    protected function existsById(int $id): bool
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
}