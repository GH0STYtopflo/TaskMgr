<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\dto\user\CreateUserDTO;
use ghosty\taskmgr\dto\user\FindUserByIdDTO;
use ghosty\taskmgr\dto\user\UpdatePasswordDTO;
use ghosty\taskmgr\dto\user\UpdateUsernameDTO;
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

    public function insert(CreateUserDTO | DTO $data): array
    {
        $template = "INSERT INTO users (username, password_hash, is_admin) VALUES (:username, :password_hash, FALSE) RETURNING id, username, is_admin";

        // encode user password to prevent storing plain_text
        $password_hash = PasswordEncoder::encode($data->getPassword());

        $data = $data->toArray() + ['password_hash' => $password_hash];

        // Unused param
        unset($data['password']);

        try {
            return $this->handle->preparedStatement($template, $data)->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function findById(DTO | FindUserByIdDTO $data): ?array
    {
        $template = "SELECT * FROM users WHERE id = :id";

        try {
            $result = $this->handle->preparedStatement($template, $data->toArray())->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }

        if (empty($result)) {
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

    public function update(DTO | UpdateUsernameDTO | UpdatePasswordDTO $data): array
    {
        if (!$this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException($data->getId(), 'users', line: __LINE__);
        }

        $data = $data->toArray();

        if (array_key_exists('new_password',$data)) {
            $data += ['password_hash' => PasswordEncoder::encode($data['new_password'])];
        }

        if (array_key_exists('new_username', $data)) {
            $data += ['username' => $data['new_username']];
        }

        unset($data['new_password']);
        unset($data['new_username']);

        try {
            $affected = $this->handle->preparedStatement(
                "UPDATE users SET 
                 username = COALESCE(:username, username), 
                 password_hash = COALESCE(:password_hash, password_hash), 
                 is_admin = COALESCE(:is_admin, is_admin)
                 WHERE id = :id
                 RETURNING id, username, is_admin",
                $data
            )->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }

        return $affected;
    }

    public function delete(FindUserByIdDTO | DTO $data): void
    {
        if (!$this->existsById($data->getId())) {
            throw new AccessingNonExistentRecordException($data->getId(), 'users', line: __LINE__);
        }

        try {
            $this->handle->preparedStatement(
                "DELETE FROM users WHERE id = :id",
                $data->toArray()
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function search(DTO $data): array
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT * FROM users WHERE (:username IS NULL OR username LIKE :username)",
                $data->toArray()
            )->fetchAll();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
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

    public function existsByUsername(string $username): bool
    {
        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM users WHERE username = :username)",
                ['username' => $username]
            )->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function findByUsername(string $username): ?array
    {
        try {
            $user = $this->handle->preparedStatement("SELECT * FROM users WHERE username = :username",
            ['username' => $username])->fetch();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }

        if (empty($user)) {
            return null;
        } else return $user;
    }
}