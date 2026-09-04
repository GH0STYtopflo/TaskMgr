<?php

namespace ghosty\taskmgr\services;

use ghosty\taskmgr\bridge\authentication\JWT;
use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\task\TaskDTO;
use ghosty\taskmgr\dto\user\CreateUserDTO;
use ghosty\taskmgr\dto\user\FindUserByIdDTO;
use ghosty\taskmgr\dto\user\LoginDTO;
use ghosty\taskmgr\dto\user\LoginResponseDTO;
use ghosty\taskmgr\dto\user\LogoutDTO;
use ghosty\taskmgr\dto\user\SearchUserDTO;
use ghosty\taskmgr\dto\user\UpdatePasswordDTO;
use ghosty\taskmgr\dto\user\UpdateUsernameDTO;
use ghosty\taskmgr\dto\user\UserDTO;
use ghosty\taskmgr\exceptions\AccessingNonAuthorizedResourceException;
use ghosty\taskmgr\exceptions\AccessingNonExistentResourceException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\InvalidCredentials;
use ghosty\taskmgr\exceptions\UsernameExistsException;
use ghosty\taskmgr\logger\Severity;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\models\UserModel;
use ghosty\taskmgr\util\PasswordEncoder;

class UserService
{
    private UserModel $userModel;
    private TaskModel $taskModel;

    private DBHandle $handle;

    private JWT $jwt;

    public function __construct(UserModel $userModel, TaskModel $taskModel, JWT $jwt, DBHandle $handle)
    {
        $this->userModel = $userModel;
        $this->taskModel = $taskModel;
        $this->jwt = $jwt;
        $this->handle = $handle;
    }

    public function createUser(CreateUserDTO $dto): UserDTO
    {
        if ($this->userModel->existsByUsername($dto->getUsername())) {
            throw new UsernameExistsException($dto->getUsername(), __LINE__);
        }

        $created = $this->userModel->insert($dto);

        return UserDTO::fromArray($created);
    }

    public function login(LoginDTO $dto): LoginResponseDTO
    {
        try {
            $user = $this->userModel->findByUsername($dto->getUsername());
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        if (is_null($user)) {
            throw new InvalidCredentials(line: __LINE__);
        }

        if (!PasswordEncoder::matches($dto->getPassword(), $user['password_hash'])) {
            throw new InvalidCredentials(line: __LINE__);
        }

        return new LoginResponseDTO($this->jwt->generateToken(UserDTO::fromArray($user)));
    }

    // This defeats the whole point of jwt auth, but it's required in the project specifications
    public function logout(LogoutDTO $dto): void
    {
        try {
            $exists = $this->handle->preparedStatement(
                "SELECT EXISTS (SELECT 1 FROM token_black_list where token = :token)",
                $dto->toArray()
            )->fetchColumn();
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }

        if ($exists) {
            return;
        }

        try {
            $this->handle->preparedStatement(
                "INSERT INTO token_black_list (token) VALUES (:token)",
                $dto->toArray()
            );
        } catch (DatabaseException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
        }
    }

    public function deleteUser(FindUserByIdDTO $dto, AuthorizationContext $context): void
    {
        if (!($context->isAdmin() || $context->getId() == $dto->getId())) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $this->userModel->delete($dto);
    }

    public function getUserById(FindUserByIdDTO $dto, AuthorizationContext $context): ?UserDTO
    {
        if (!($context->isAdmin() || $context->getId() == $dto->getId())) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $user = $this->userModel->findById($dto);

        if (is_null($user)) {
            return null;
        }

        return UserDTO::fromArray($user);
    }

    public function getAllUSers(): array
    {
        $users = $this->userModel->findAll();

        foreach ($users as &$user) {
            $user = UserDTO::fromArray($user);
        }

        return $users;
    }

    public function updateUsername(UpdateUsernameDTO $dto, AuthorizationContext $context): UserDTO
    {
        if (!($context->isAdmin() || $context->getId() == $dto->getId())) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        if ($this->userModel->existsByUsername($dto->getNewUsername())) {
            throw new UsernameExistsException($dto->getNewUsername(), __LINE__);
        }

        $affected = $this->userModel->update($dto);

        return UserDTO::fromArray($affected);
    }

    public function updatePassword(UpdatePasswordDTO $dto, AuthorizationContext $context): void
    {
        if (!($context->isAdmin() || $context->getId() == $dto->getId())) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $this->userModel->update($dto);
    }

    public function getUserTasks(FindUserByIdDTO $dto, AuthorizationContext $context): array
    {
        if (!($context->isAdmin() || $context->getId() == $dto->getId())) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        if (!$this->userModel->existsById($dto->getId())) {
            throw new AccessingNonExistentResourceException($dto->getId(), 'users', line: __LINE__);
        }

        $tasks = $this->taskModel->getUserTasks($dto);

        foreach ($tasks as &$task) {
            $task = TaskDTO::fromArray($task);
        }

        return $tasks;
    }

    public function searchUsers(SearchUserDTO $dto): array
    {
        $users = $this->userModel->search($dto);

        foreach ($users as &$user) {
            $user = UserDTO::fromArray($user);
        }

        return $users;
    }
}