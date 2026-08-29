<?php

namespace ghosty\taskmgr\models;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\util\PasswordEncoder;

class UserModel extends Model
{
    public function __construct(DBHandle $handle)
    {
        parent::__construct($handle);
    }

    public function insert(array $data): void
    {
        $template = "INSERT INTO users (username, password_hash, is_admin) VALUES (:username, :password_hash, :is_admin)";

        // encode user password to prevent storing plain_text
        $data["password_hash"] = PasswordEncoder::encode($data["password_hash"]);

        $this->handle->preparedStatement($template, $data);
    }

    public function findById(int $id): ?array
    {
        $template = "SELECT * FROM users WHERE id = :id";

        $result = $this->handle->preparedStatement($template, ['id' => $id])->fetch();

        if (!$result) {
            return null;
        } else return $result;
    }

    public function findAll(): array
    {
        return $this->handle->query("SELECT * FROM users")->fetchAll();
    }

    public function update(float $id, array $data): void
    {
        $this->handle->preparedStatement(
            "UPDATE users SET 
                 username = COALESCE(:username, username), 
                 password_hash = COALESCE(:password_hash, password_hash) 
                 WHERE id = :id",
            $data + ['id' => $id]
        );
    }

    public function delete(int $id): void
    {
        $this->handle->preparedStatement(
            "DELETE FROM users WHERE id = :id",
            ['id' => $id]
        );
    }

    public function search(array $data): array
    {
        return $this->handle->preparedStatement(
            "SELECT * FROM users WHERE (:username IS NULL OR username LIKE :username)",
            $data
        )->fetchAll();
    }
}