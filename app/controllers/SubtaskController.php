<?php

namespace ghosty\taskmgr\controllers;

use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\dto\subtask\CreateSubtaskDTO;
use ghosty\taskmgr\dto\subtask\FindSubtaskById;
use ghosty\taskmgr\dto\subtask\GetTaskSubtask;
use ghosty\taskmgr\dto\subtask\SearchSubtaskDTO;
use ghosty\taskmgr\dto\subtask\SetSubtaskStatusDTO;
use ghosty\taskmgr\dto\subtask\UpdateSubtaskTitleDTO;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\services\SubtaskService;
use ghosty\taskmgr\util\HTTP\Headers;

class SubtaskController
{
    private SubtaskService $subtaskService;

    public function __construct(SubtaskService $subtaskService)
    {
        $this->subtaskService = $subtaskService;
    }

    /**
     * Maps to: Maps to: POST /tasks/{task_id}/subtasks
     *
     * @param array $data
     * @return Response
     */
    public function createSubtask(array $data): Response
    {
        try {
            $dto = CreateSubTaskDto::fromArray($data);
            $response = $this->subtaskService->createSubtask($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON],
            $response
        );
    }

    /**
     * Maps to: Maps to: POST /subtasks/{id}
     *
     * @param array $data
     * @return Response
     */
    public function deleteSubtask(array $data): Response
    {
        try {
            $dto = FindSubtaskById::fromArray($data);
            $this->subtaskService->deleteSubtask($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: GET /subtasks/{id}
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function getSubtaskById(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindSubtaskById::fromArray($data);
            $response = $this->subtaskService->getSubtaskById($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: GET /subtasks
     *
     * @return Response
     */
    public function getAllSubtasks(): Response
    {
        try {
            $response = $this->subtaskService->getAllSubtasks();
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: GET /tasks/{task_id}/subtasks
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function getTaskSubtasks(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = GetTaskSubtask::fromArray($data);
            $response = $this->subtaskService->getTaskSubtasks($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: PATCH /subtasks/{id}
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function updateSubtaskStatus(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = SetSubtaskStatusDto::fromArray($data);
            $response = $this->subtaskService->updateSubtaskStatus($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * public PUT /subtasks/{id}
     *
     * @param array $data
     * @return Response
     */
    public function updateSubtaskTitle(array $data): Response
    {
        try {
            $dto = UpdateSubtaskTitleDTO::fromArray($data);
            $response = $this->subtaskService->updateSubtaskTitle($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: GET /subtasks?query
     *
     * @param array $data
     * @return Response
     */
    public function searchSubtasks(array $data): Response
    {
        try {
            $dto = SearchSubtaskDto::fromArray($data);
            $response = $this->subtaskService->searchSubtasks($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }
}