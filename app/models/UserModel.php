<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\user\CreateUserDTO;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\dto\user\FindUserByIdDTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\logger\Severity;
use ghosty\taskmgr\util\PasswordEncoder;
use PDOException;

class UserModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(CreateUserDTO | DTO $data): void
    {
        $template = "INSERT INTO users (username, password_hash, is_admin) VALUES (:username, :password_hash, :is_admin)";

        // encode user password to prevent storing plain_text
        $password_hash = PasswordEncoder::encode($data->getPassword());

        try {
            $this->handle->preparedStatement($template, $data->toArray() + ['password_hash' => $password_hash]);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function findById(DTO $data): ?array
    {
        $template = "SELECT * FROM users WHERE id = :id";

        try {
            $result = $this->handle->preparedStatement($template, $data->toArray())->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }

        if (!$result) {
            return null;
        } else return $result;
    }

    public function findAll(): array
    {
        try {
            return $this->handle->query("SELECT * FROM users")->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function update(DTO $data): void
    {
        if (!$this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException($data->getId(), 'users', line: __LINE__);
        }

        $data = $data->toArray();

        if (array_key_exists('new_password',$data)) {
            $data += ['password_hash' => PasswordEncoder::encode($data['new_password'])];
        }

        try {
            $this->handle->preparedStatement(
                "UPDATE users SET 
                 username = COALESCE(:new_username, username), 
                 password_hash = COALESCE(:password_hash, password_hash), 
                 is_admin = (:is_admin, is_admin)",
                $data
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function delete(FindUserByIdDTO | DTO $data): void
    {
        if (!$this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException($data->getId(), 'users', line: __LINE__);
        }

        $this->handle->preparedStatement(
            "DELETE FROM users WHERE id = :id",
            ['id' => $data->getId()]
        );
    }

    public function search(DTO $data): array
    {
        return $this->handle->preparedStatement(
            "SELECT * FROM users WHERE (:username IS NULL OR username LIKE :username)",
            $data
        )->fetchAll();
    }

    public function existsById(int $id): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM users WHERE id = :id)",
                ['id' => $id]
            )->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }
}