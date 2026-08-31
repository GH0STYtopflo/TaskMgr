<?php

namespace ghosty\taskmgr\controllers;

use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\dto\subtask\CreateSubtaskDTO;
use ghosty\taskmgr\dto\subtask\SearchSubtaskDTO;
use ghosty\taskmgr\dto\subtask\UpdateSubtaskTitleDTO;
use ghosty\taskmgr\dto\subtask\FindSubtaskById;
use ghosty\taskmgr\dto\subtask\GetTaskSubtask;
use ghosty\taskmgr\dto\subtask\SetSubtaskStatusDTO;
use ghosty\taskmgr\dto\subtask\SubtaskDTO;
use ghosty\taskmgr\dto\task\TaskDTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\exceptions\SubtaskExistsException;
use ghosty\taskmgr\models\SubTaskModel;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\util\HTTP\Headers;

class SubtaskController
{
    private SubTaskModel $subTaskModel;
    private TaskModel $taskModel;

    /**
     * @param SubTaskModel $subTaskModel
     * @param TaskModel $taskModel
     */
    public function __construct(SubTaskModel $subTaskModel, TaskModel $taskModel)
    {
        $this->subTaskModel = $subTaskModel;
        $this->taskModel = $taskModel;
    }

    public function createSubtask(array $data): Response
    {
        try {
            $dto = CreateSubTaskDto::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        if ($this->subTaskModel->existsByTitle($dto->getTitle())) {
            new SubtaskExistsException($dto->getTitle(), line: __LINE__)->createErrResponse();
        }

        if ($this->taskModel->existsById($dto->getTaskId())) {
            new AccessingNonExistentRecordException($dto->getTaskID(), 'tasks', line: __LINE__)->createErrResponse();
        }

        try {
            $this->subTaskModel->insert($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON]
        );
    }

    public function deleteSubtask(array $data): Response
    {
        try {
            $dto = FindSubtaskById::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->subTaskModel->delete($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function getSubtaskById(array $data): Response
    {
        try {
            $dto = FindSubtaskById::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $subtask = $this->subTaskModel->findById($dto->getId());
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], SubtaskDTO::fromArray($subtask));
    }

    public function getAllSubtasks(array $data): Response
    {
        try {
            $tasks = $this->subTaskModel->findAll();
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        foreach ($tasks as &$task) {
            $task = TaskDTO::fromArray($task);
        }


        return Response::makeResponse(200, [Headers::TYPE_JSON], $tasks);
    }

    public function getTaskSubtasks(array $data): Response
    {
        try {
            $dto = GetTaskSubtask::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $subtasks = $this->subTaskModel->search($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        foreach ($subtasks as &$subtask) {
            $subtask = SubtaskDTO::fromArray($subtask);
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $subtasks);
    }

    public function updateSubtaskStatus(array $data): Response
    {
        try {
            $dto = SetSubtaskStatusDto::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->subTaskModel->update($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function updateSubtaskTitle(array $data): Response
    {
        try {
            $dto = UpdateSubtaskTitleDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->subTaskModel->update($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function searchSubtasks(array $data): Response
    {
        try {
            $dto = SearchSubtaskDto::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $subtasks = $this->subTaskModel->search($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        foreach ($subtasks as &$subtask) {
            $subtask = SubtaskDTO::fromArray($subtask);
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $subtasks);
    }
}