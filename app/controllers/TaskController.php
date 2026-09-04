<?php

namespace ghosty\taskmgr\controllers;

use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\dto\task\AddAndRemoveTaskCategory;
use ghosty\taskmgr\dto\task\AssignAndDischargeTaskDTO;
use ghosty\taskmgr\dto\task\CreateTaskDTO;
use ghosty\taskmgr\dto\task\FindTaskByIdDTO;
use ghosty\taskmgr\dto\task\SearchTaskDTO;
use ghosty\taskmgr\dto\task\UpdateTaskDTO;
use ghosty\taskmgr\dto\task\UpdateTaskStatusDTO;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\services\TaskService;
use ghosty\taskmgr\util\HTTP\Headers;

class TaskController
{
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Maps to: POST /tasks
     *
     * @param array $taskData
     * @return Response
     */
    public function createTask(array $taskData): Response
    {
        try {
            $dto = CreateTaskDTO::fromArray($taskData);
            $response = $this->taskService->createTask($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(201, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: DELETE /tasks/{id}
     *
     * @param array $data
     * @return Response
     */
    public function deleteTask(array $data): Response
    {
        try {
            $dto = FindTaskByIdDTO::fromArray($data);
            $this->taskService->deleteTask($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: GET /tasks/{id}
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function getTaskById(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindTaskByIdDTO::fromArray($data);
            $response = $this->taskService->getTaskById($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: GET /tasks
     *
     * @return Response
     */
    public function getAllTasks(): Response
    {
        try {
            $response = $this->taskService->getAllTasks();
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: PATCH /tasks/{id}
     *
     * @param array $data
     * @return Response
     */
    public function updateTask(array $data): Response
    {
        try {
            $dto = UpdateTaskDTO::fromArray($data);
            $response = $this->taskService->updateTask($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: GET /tasks?query
     *
     * @param array $data
     * @return Response
     */
    public function searchTasks(array $data): Response
    {
        try {
            $dto = SearchTaskDTO::fromArray($data);
            $response = $this->taskService->search($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: POST /tasks/{task_id}/users
     *
     * @param array $data
     * @return Response
     */
    public function assignTaskToUser(array $data): Response
    {
        try {
            $dto = AssignAndDischargeTaskDTO::fromArray($data);
            $response = $this->taskService->assignTaskToUser($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: DELETE /tasks/{task_id}/users
     *
     * @param array $data
     * @return Response
     */
    public function dischargeTaskFromUser(array $data): Response
    {
        try {
            $dto = AssignAndDischargeTaskDTO::fromArray($data);
            $this->taskService->disChargeUserFromTask($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }
        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: PATCH /tasks/{id}:update_status
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function updateTaskStatus(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = UpdateTaskStatusDTO::fromArray($data);
            $response = $this->taskService->updateTaskStatus($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: POST /tasks/{task_id}:add_category
     *
     * @param array $data
     * @return Response
     */
    public function addTaskCategory(array $data): Response
    {
        try {
            $dto = AddAndRemoveTaskCategory::fromArray($data);
            $response = $this->taskService->addTaskCategory($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: DELETE /tasks/{task_id}:remove_category
     *
     * @param array $data
     * @return Response
     */
    public function removeTaskCategory(array $data): Response
    {
        try {
            $dto = AddAndRemoveTaskCategory::fromArray($data);
            $this->taskService->removeTaskCategory($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }
}