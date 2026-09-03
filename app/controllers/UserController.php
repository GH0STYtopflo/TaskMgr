<?php

namespace ghosty\taskmgr\controllers;

use ghosty\taskmgr\bridge\authentication\JWT;
use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\dto\task\TaskDTO;
use ghosty\taskmgr\dto\user\CreateUserDTO;
use ghosty\taskmgr\dto\user\FindUserByIdDTO;
use ghosty\taskmgr\dto\user\LoginDTO;
use ghosty\taskmgr\dto\user\LoginResponseDTO;
use ghosty\taskmgr\dto\user\UpdatePasswordDTO;
use ghosty\taskmgr\dto\user\UpdateUsernameDTO;
use ghosty\taskmgr\dto\user\UserDTO;
use ghosty\taskmgr\exceptions\AccessingNonAuthorizedResourceException;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\exceptions\InvalidCredentials;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\models\UserModel;
use ghosty\taskmgr\util\HTTP\Headers;
use ghosty\taskmgr\util\PasswordEncoder;

class UserController
{
    private UserModel $userModel;
    private TaskModel $taskModel;

    public function __construct(UserModel $userModel, TaskModel $taskModel)
    {
        $this->userModel = $userModel;
        $this->taskModel = $taskModel;
    }

    // I should probably use attributes but PHPDoc will do just fine. I'm not planning on using reflection
    /**
     * Maps to: POST /sign-up
     *
     * @param array $data
     * @return Response
     */
    public function createUser(array $data): Response
    {
        try {
            $dto = CreateUserDto::fromArray($data);
        } catch (MissingParamException $e) {
            return $e->createErrResponse();
        }

        try {
            $this->userModel->insert($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(201, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: POST /login
     *
     * @param array $data
     * @return Response
     */
    public function login(array $data): Response
    {
        try {
            $dto = LoginDto::fromArray($data);
        } catch (MissingParamException $e) {
            return $e->createErrResponse();
        }

        try {
            $user = $this->userModel->findByUsername($dto->getUsername());
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        if (is_null($user)) {
            return new InvalidCredentials(line: __LINE__)->createErrResponse();
        }

        if (!PasswordEncoder::matches($dto->getPassword(), $user['password_hash'])) {
            return new InvalidCredentials(line: __LINE__)->createErrResponse();
        }

        $response = new LoginResponseDTO(JWT::generateToken(UserDTO::fromArray($user)));

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: DELETE /users/{id}
     *
     * @param array $data
     * @return Response
     */
    public function deleteUser(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindUserByIdDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        if (!($context->isAdmin() || $context->getId() == $data['id'])) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        try {
            $this->userModel->delete($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: GET /users/{id}
     *
     * @param array $data
     * @return Response
     */
    public function getUserById(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindUserByIdDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $user = $this->userModel->findById($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        if (!($context->isAdmin() || $context->getId() == $user['id'])) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], is_null($user) ? null : UserDTO::fromArray($user));
    }

    /**
     * Maps to: GET /users
     *
     * @return Response
     */
    public function getAllUsers(): Response
    {
        try {
            $users = $this->userModel->findAll();
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        foreach ($users as &$user) {
            $user = UserDTO::fromArray($user);
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $users);
    }

    /**
     * Maps to PATCH /users/{id}/update_username
     *
     * @param array $data
     * @return Response
     */
    public function updateUsername(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = UpdateUsernameDto::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        if (!($context->isAdmin() || $context->getId() == $data['id'])) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        try {
            $this->userModel->update($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: PATCH /users/{id}/update_password
     *
     * @param array $data
     * @return Response
     */
    public function updatePassword(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = UpdatePasswordDto::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        if (!($context->isAdmin() || $context->getId() == $data['id'])) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        try {
            $this->userModel->update($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: GET /users/{id}/tasks
     *
     * @param array $data
     * @return Response
     */
    public function getUserTasks(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindUserByIdDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        if (!($context->isAdmin() || $context->getId() == $data['id'])) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        if (!$this->userModel->existsById($dto->getId())) {
            return new AccessingNonExistentRecordException($dto->getId(), 'users', line: __LINE__)->createErrResponse();
        }

        try {
            $tasks = $this->taskModel->getUserTasks($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        foreach ($tasks as &$task) {
            $task = TaskDTO::fromArray($task);
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $tasks);
    }
}