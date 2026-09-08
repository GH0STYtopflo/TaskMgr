<?php

namespace ghosty\taskmgr\services;

use DateTimeImmutable;
use Exception;
use ghosty\taskmgr\bridge\authentication\JWT;
use ghosty\taskmgr\database\custom_types\ActionStatus;
use ghosty\taskmgr\database\custom_types\ResourceType;
use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\log\LogDTO;
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
use ghosty\taskmgr\models\LogModel;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\models\UserModel;
use ghosty\taskmgr\util\PasswordEncoder;

class UserService
{
    private UserModel $userModel;
    private TaskModel $taskModel;

    private DBHandle $handle;

    private JWT $jwt;
    private LogModel $logModel;

    public function __construct(UserModel $userModel, TaskModel $taskModel, JWT $jwt, DBHandle $handle, LogModel $logModel)
    {
        $this->userModel = $userModel;
        $this->taskModel = $taskModel;
        $this->jwt = $jwt;
        $this->handle = $handle;
        $this->logModel = $logModel;
    }

    public function createUser(CreateUserDTO $dto): UserDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if ($this->userModel->existsByUsername($dto->getUsername())) {
                throw new UsernameExistsException($dto->getUsername(), __LINE__);
            }
            $created = UserDTO::fromArray($this->userModel->insert($dto));

            $log->setUserId($created->getId())->
            setResourceId($created->getId())->
            setDescription("Created user {$dto->getUsername()}")
            ->setActionStatus(ActionStatus::SUCCESS);

            return $created;
        } catch (Exception $e) {
            $log->setUserId(-1)
                ->setDescription("Failed to create user {$dto->getUsername()}. reason: " . $e->getMessage())
                ->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function login(LoginDTO $dto): LoginResponseDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            $user = $this->userModel->findByUsername($dto->getUsername());

            if (is_null($user)) {
                throw new InvalidCredentials(line: __LINE__);
            }

            if (!PasswordEncoder::matches($dto->getPassword(), $user['password_hash'])) {
                throw new InvalidCredentials(line: __LINE__);
            }

            $user = UserDTO::fromArray($user);

            $log->setUserId($user->getId())
                ->setResourceId($user->getId())
                ->setActionStatus(ActionStatus::SUCCESS)
            ->setDescription("User {$user->getId()} logged in");

            return new LoginResponseDTO($this->jwt->generateToken($user));
        } catch (Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)
                ->setUserId(-1)
                ->setResourceId((isset($user) && $e instanceof AccessingNonExistentResourceException) ? null : $user->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    // This defeats the whole point of jwt auth, but it's required in the project specifications
    public function logout(LogoutDTO $dto, AuthorizationContext $context): void
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::USER)->setTimestamp(new DateTimeImmutable('now'))
        ->setUserId($context->getId());

        try {
            $user = $this->userModel->findById(new FindUserByIdDTO($context->getId()));
            if (empty($user)) {
                throw new AccessingNonExistentResourceException($context->getId());
            }
            if (!PasswordEncoder::matches($dto->getPassword(), $user['password_hash'])) {
                throw new InvalidCredentials(line: __LINE__);
            }
            try {
                $exists = $this->handle->preparedStatement(
                    "SELECT EXISTS (SELECT 1 FROM token_black_list where token = :token)",
                    ['token' => $dto->getToken()]
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
                    ['token' => $dto->getToken()]
                );
            } catch (DatabaseException $e) {
                throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e, __LINE__);
            }

            $log->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($context->getId())
                ->setDescription("User {$context->getId()} logged out}");
        } catch (Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)
                ->setUserId($context->getId())
                ->setResourceId($context->getId())
                ->setDescription("User {$context->getId()} failed to logout. reason: " . $e->getMessage());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function deleteUser(FindUserByIdDTO $dto, AuthorizationContext $context): void
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'))
            ->setUserId($context->getId());

        try {
            if (!($context->isAdmin() || $context->getId() == $dto->getId())) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $this->userModel->delete($dto);

            $log->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($dto->getId())
                ->setDescription("Deleted user {$dto->getId()}");
        } catch (Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId())
                ->setDescription("Failed to delete user {$dto->getId()}. reason: " . $e->getMessage());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getUserById(FindUserByIdDTO $dto, AuthorizationContext $context): ?UserDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'))
            ->setUserId($context->getId());

        try {
            if (!($context->isAdmin() || $context->getId() == $dto->getId())) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $user = $this->userModel->findById($dto);
            if (is_null($user)) {
                return null;
            }

            $log->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($dto->getId())
                ->setDescription("Fetched user {$dto->getId()}");

            return UserDTO::fromArray($user);
        } catch (Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)
                ->setDescription(
                    "Failed to fetch user {$dto->getId()}. reason: " . $e->getMessage()
                )
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getAllUsers(AuthorizationContext $context): array
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'))
            ->setUserId($context->getId());

        try {
            $users = $this->userModel->findAll();
            foreach ($users as &$user) {
                $user = UserDTO::fromArray($user);
            }

            $log->setActionStatus(ActionStatus::SUCCESS)
                ->setDescription("Fetched all users");

            return $users;
        } catch (Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)
                ->setDescription(
                    "Failed to fetch all users. reason: " . $e->getMessage()
                );

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function updateUsername(UpdateUsernameDTO $dto, AuthorizationContext $context): UserDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'))
            ->setUserId($context->getId());

        try {
            if (!($context->isAdmin() || $context->getId() == $dto->getId())) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $user = $this->userModel->findById(new FindUserByIdDTO($context->getId()));
            if (empty($user)) {
                throw new AccessingNonExistentResourceException($context->getId());
            }
            if (!PasswordEncoder::matches($dto->getPassword(), $user['password_hash'])) {
                throw new InvalidCredentials(line: __LINE__);
            }
            if ($this->userModel->existsByUsername($dto->getNewUsername())) {
                throw new UsernameExistsException($dto->getNewUsername(), __LINE__);
            }
            $affected = UserDTO::fromArray($this->userModel->update($dto));

            $log->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($dto->getId())
                ->setDescription("Updated username of user {$dto->getId()} to {$dto->getNewUsername()}");

            return $affected;
        } catch (Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId())
                ->setDescription(
                    "Failed to update username of user {$dto->getId()}. reason: " . $e->getMessage()
                );

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function updatePassword(UpdatePasswordDTO $dto, AuthorizationContext $context): void
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'))
            ->setUserId($context->getId());

        try {
            if (!($context->isAdmin() || $context->getId() == $dto->getId())) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $user = $this->userModel->findById(new FindUserByIdDTO($context->getId()));
            if (empty($user)) {
                throw new AccessingNonExistentResourceException($context->getId());
            }
            if (!PasswordEncoder::matches($dto->getOldPassword(), $user['password_hash'])) {
                throw new InvalidCredentials(line: __LINE__);
            }
            $this->userModel->update($dto);

            $log->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($dto->getId())
                ->setDescription(
                    "Updated password of user {$dto->getId()}"
                );
        } catch (Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)
                ->setDescription(
                    "Failed to update password of user {$dto->getId()}. reason: " . $e->getMessage()
                )
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getUserTasks(FindUserByIdDTO $dto, AuthorizationContext $context): array
    {
        $logA = LogDTO::builder()->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'))
            ->setUserId($context->getId());
        $logB = LogDTO::builder()->setResourceType(ResourceType::TASK)
            ->setTimestamp(new DateTimeImmutable('now'))
            ->setUserId($context->getId());

        try {
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

            $logA->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($dto->getId())
                ->setDescription(
                    "Fetched tasks for user {$dto->getId()}"
                );

            $logB->setActionStatus(ActionStatus::SUCCESS)
                ->setDescription(
                    "Fetched tasks for user {$dto->getId()}"
                );

            return $tasks;
        } catch (Exception $e) {
            $logA->setActionStatus(ActionStatus::FAILURE)
                ->setDescription("Failed to fetch tasks for user {$dto->getId()}. reason: " . $e->getMessage())
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            $logB->setActionStatus(ActionStatus::FAILURE)
                ->setDescription(
                    "Failed to fetch tasks for user {$dto->getId()}. reason: " . $e->getMessage()
                );
            throw $e;
        } finally {
            $this->logModel->log($logA);
            $this->logModel->log($logB);
        }
    }

    public function searchUsers(SearchUserDTO $dto, AuthorizationContext $context): array
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'))
            ->setUserId($context->getId());

        try {
            $users = $this->userModel->search($dto);
            foreach ($users as &$user) {
                $user = UserDTO::fromArray($user);
            }

            $log->setActionStatus(ActionStatus::SUCCESS)
                ->setDescription("Queried tasks with params: " . json_encode($dto));

            return $users;
        } catch (Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)
                ->setDescription("Failed to search tasks with params: " . json_encode($dto) . ". reason: " . $e->getMessage());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }
}