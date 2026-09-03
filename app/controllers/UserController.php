<?php

namespace ghosty\taskmgr\controllers;

use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\dto\user\CreateUserDTO;
use ghosty\taskmgr\dto\user\FindUserByIdDTO;
use ghosty\taskmgr\dto\user\LoginDTO;
use ghosty\taskmgr\dto\user\LogoutDTO;
use ghosty\taskmgr\dto\user\UpdatePasswordDTO;
use ghosty\taskmgr\dto\user\UpdateUsernameDTO;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\services\UserService;
use ghosty\taskmgr\util\HTTP\Headers;

class UserController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
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
            $response = $this->userService->createUser($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(201, [Headers::TYPE_JSON], $response);
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
            $response = $this->userService->login($dto);
        } catch (MissingParamException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    public function logout(array $data): Response
    {
        try {
            $dto = LogoutDto::fromArray($data);
            $this->userService->logout($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: DELETE /users/{id}
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function deleteUser(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindUserByIdDTO::fromArray($data);
            $this->userService->deleteUser($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: GET /users/{id}
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function getUserById(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindUserByIdDTO::fromArray($data);
            $response = $this->userService->getUserById($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: GET /users
     *
     * @return Response
     */
    public function getAllUsers(): Response
    {
        try {
            $response = $this->userService->getAllUsers();
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to PATCH /users/{id}/update_username
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function updateUsername(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = UpdateUsernameDto::fromArray($data);
            $response = $this->userService->updateUsername($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: PATCH /users/{id}/update_password
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function updatePassword(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = UpdatePasswordDto::fromArray($data);
            $this->userService->updatePassword($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: GET /users/{id}/tasks
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function getUserTasks(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindUserByIdDTO::fromArray($data);
            $response = $this->userService->getUserTasks($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }
}