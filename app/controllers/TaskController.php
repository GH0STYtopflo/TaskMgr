<?php

namespace ghosty\taskmgr\controllers;

use Exception;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\dto\task\AddAndRemoveTaskCategory;
use ghosty\taskmgr\dto\task\AssignAndDischargeTaskDTO;
use ghosty\taskmgr\dto\task\CreateTaskDTO;
use ghosty\taskmgr\dto\task\FindTaskByIdDTO;
use ghosty\taskmgr\dto\task\SearchTaskByTitleDTO;
use ghosty\taskmgr\dto\task\TaskDTO;
use ghosty\taskmgr\dto\task\UpdateTaskDTO;
use ghosty\taskmgr\dto\task\UpdateTaskStatusDTO;
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

    public function __construct(TaskModel $taskModel, UserModel $userModel, CategoryModel $categoryModel)
    {
        $this->taskModel = $taskModel;
        $this->userModel = $userModel;
        $this->categoryModel = $categoryModel;
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
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->taskModel->insert($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
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

    /**
     * Maps to: GET /tasks/{id}
     *
     * @param array $data
     * @return Response
     */
    public function getTaskById(array $data): Response
    {
        try {
            $dto = FindTaskByIdDTO::fromArray($data);
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

    /**
     * Maps to: GET /tasks
     *
     * @return Response
     */
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
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->taskModel->update($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: GET /tasks/search
     *
     * @param array $data
     * @return Response
     */
    public function searchTasksByTitle(array $data): Response
    {
        try {
            $dto = SearchTaskByTitleDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $tasks = $this->taskModel->search($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        foreach ($tasks as &$task) {
            $task = TaskDTO::fromArray($task);
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $tasks);
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
        } catch (ExceptionTemplate $e) {
            $e->createErrResponse();
        }

        // Cross-Model logic must be handled in controller
        if ($this->taskModel->existsById($dto->getTaskId())) {
            return new AccessingNonExistentRecordException($dto->getTaskId(), 'tasks', line: __LINE__)->createErrResponse();
        }

        if ($this->userModel->existsById($dto->getUserId())) {
            return new AccessingNonExistentRecordException($dto->getUserId(), 'users', line: __LINE__)->createErrResponse();
        }
        //----------------------------------------------------

        try {
            $this->taskModel->assignTaskToUser($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
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
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->taskModel->dischargeUserFromTask($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }
        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: PATCH /tasks/{id}/update-status
     *
     * @param array $data
     * @return Response
     */
    public function updateTaskStatus(array $data): Response
    {
        try {
            $dto = UpdateTaskStatusDTO::fromArray($data);
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

    /**
     * Maps to: POST /tasks/{task_id}/categories
     *
     * @param array $data
     * @return Response
     */
    public function addTaskCategory(array $data): Response
    {
        try {
            $dto = AddAndRemoveTaskCategory::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        // Cross entity-Model logic. (I could put this into a service class, but I'm not sure if that's what MVC is all about)
        if ($this->taskModel->existsById($dto->getTaskId())) {
            return new AccessingNonExistentRecordException($dto->getTaskId(), 'tasks', line: __LINE__)->createErrResponse();
        }

        if ($this->categoryModel->existsById($dto->getCategoryId())) {
            return new AccessingNonExistentRecordException($dto->getCategoryId(), 'categories', line: __LINE__)->createErrResponse();
        }

        try {
            $this->taskModel->addTaskCategory($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: DELETE /tasks/{task_id}/categories
     *
     * @param array $data
     * @return Response
     */
    public function removeTaskCategory(array $data): Response
    {
        try {
            $dto = AddAndRemoveTaskCategory::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->taskModel->removeTaskCategory($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }
}