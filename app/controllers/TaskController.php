<?php

namespace ghosty\taskmgr\controllers;

use Exception;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\dto\task\AssignAndDischargeTaskDTO;
use ghosty\taskmgr\dto\task\CreateTaskDTO;
use ghosty\taskmgr\dto\task\FindTaskByIdDTO;
use ghosty\taskmgr\dto\task\TaskDTO;
use ghosty\taskmgr\dto\task\UpdateTaskDTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\exceptions\MalformedDateException;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;
use ghosty\taskmgr\models\CategoryModel;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\util\HTTP\Headers;
use ghosty\taskmgr\models\UserModel;

class TaskController
{
    private TaskModel $taskModel;
    private UserModel $userModel;
    private CategoryModel $categoryModel;

    public function __construct(TaskModel $taskModel, UserModel $userModel)
    {
        $this->taskModel = $taskModel;
        $this->userModel = $userModel;
    }

    public function createTask(array $taskData): Response
    {
        try {
            $dto = CreateTaskDTO::fromArray($taskData);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->taskModel->insert($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function deleteTask(int $taskId): Response
    {
        try {
            $dto = FindTaskByIdDTO::fromArray(['id' => $taskId]);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->taskModel->delete($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function getTaskById(int $taskId): Response
    {
        try {
            $dto = FindTaskByIdDTO::fromArray(['id' => $taskId]);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $task = $this->taskModel->findById($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], TaskDTO::fromArray($task));
    }

    public function getAllTasks(): Response
    {
        try {
            $tasks = $this->taskModel->findAll();
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        foreach ($tasks as &$task) {
            $task = TaskDTO::fromArray($task);
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $tasks);
    }

    public function updateTask(int $taskId, array $taskData): Response
    {
        try {
            $dto = UpdateTaskDTO::fromArray($taskData + ['id' => $taskId]);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->taskModel->update($taskId, $dto->toArray());
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function searchTasksByTitle(array $data): Response
    {
        try {
            $tasks = $this->taskModel->search($data);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        foreach ($tasks as &$task) {
            $task = TaskDTO::fromArray($task);
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $tasks);
    }

    public function assignTaskToUser(array $data): Response
    {
        try {
            $dto = AssignAndDischargeTaskDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            $e->createErrResponse();
        }

        // Cross-Model logic must be handled in controller
        if ($this->taskModel->existsById($dto->getTaskId())) {
            throw new AccessingNonExistentRecordException($dto->getTaskId(), 'tasks', line: __LINE__);
        }

        if ($this->userModel->existsById($dto->getUserId())) {
            throw new AccessingNonExistentRecordException($dto->getUserId(), 'users', line: __LINE__);
        }
        //----------------------------------------------------

        try {
            $this->taskModel->assignTaskToUser($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function dischargeTaskFromUser(array $data): Response
    {
        try {
            $dto = AssignAndDischargeTaskDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        // Cross-Model logic must be handled in controller
        if ($this->taskModel->existsById($dto->getTaskId())) {
            throw new AccessingNonExistentRecordException($dto->getTaskId(), 'tasks', line: __LINE__);
        }

        if ($this->userModel->existsById($dto->getUserId())) {
            throw new AccessingNonExistentRecordException($dto->getUserId(), 'users', line: __LINE__);
        }
        //----------------------------------------------------

        $this->taskModel->dischargeUserFromTask($dto);
        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function updateTaskStatus(array $data): Response
    {
        try {
            $dto = UpdateTaskDTO::fromArray($data);
        } catch (TypeMismatchException $e) {
            $e->createErrResponse();
        }

        try {
            $this->taskModel->updateTaskStatus($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function addTaskCategory(array $data): Response
    {
        try {
            $dto = AssignAndDischargeTaskDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        // Cross entity-Model logic. (I could put this into a service class, but I'm not sure if that's what MVC is all about)
        if ($this->taskModel->existsById($dto->getTaskId())) {
            throw new AccessingNonExistentRecordException($dto->getTaskId(), 'tasks', line: __LINE__);
        }

        if ($this->categoryModel->existsById($dto->getCategoryId())) {
            throw new AccessingNonExistentRecordException($dto->getCategoryId(), 'categories', line: __LINE__);
        }

        try {
            $this->taskModel->addTaskCategory($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function removeTaskCategory(array $data): Response
    {
        try {
            $dto = AssignAndDischargeTaskDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        if ($this->taskModel->existsById($dto->getTaskId())) {
            throw new AccessingNonExistentRecordException($dto->getTaskId(), 'tasks', line: __LINE__);
        }

        if ($this->categoryModel->existsById($dto->getCategoryId())) {
            throw new AccessingNonExistentRecordException($dto->getCategoryId(), 'categories', line: __LINE__);
        }

        try {
            $this->taskModel->removeTaskCategory($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }
}