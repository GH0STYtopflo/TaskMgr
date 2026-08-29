<?php

namespace ghosty\taskmgr\controllers;

use Exception;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\dto\task\AssignAndDischargeTaskDTO;
use ghosty\taskmgr\dto\task\CreateTaskDTO;
use ghosty\taskmgr\dto\task\TaskDTO;
use ghosty\taskmgr\dto\task\UpdateTaskDTO;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\exceptions\MalformedDateException;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\util\HTTP\Headers;
use ghosty\taskmgr\models\UserModel;

class TaskController extends Controller
{
    private TaskModel $taskModel;
    private UserModel $userModel;

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
            $this->taskModel->insert($dto->toArray());
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return new Response([Headers::TYPE_JSON], 200);
    }

    public function deleteTask(int $taskId): Response
    {
        try {
            $this->taskModel->delete($taskId);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return new Response([Headers::TYPE_JSON], 200);
    }

    public function getTaskById(int $taskId): Response
    {
        try {
            $task = $this->taskModel->findById($taskId);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return new Response([Headers::TYPE_JSON], 200, is_null($task) ? null : json_encode(TaskDTO::fromArray($task)));
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

        return new Response([Headers::TYPE_JSON], 200, json_encode($tasks));
    }

    public function updateTask(int $taskId, array $taskData): Response
    {
        try {
            $dto = UpdateTaskDTO::fromArray($taskData);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->taskModel->update($taskId, $dto->toArray());
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return new Response([Headers::TYPE_JSON], 200);
    }

    public function searchTasksByTitle(string $title): Response
    {
        try {
            $tasks = $this->taskModel->search(['title' => $title]);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return new Response([Headers::TYPE_JSON], 200, json_encode(TaskDTO::fromArray($tasks)));
    }

    public function assignTaskToUser(array $data): Response
    {
        try {
            $dto = AssignAndDischargeTaskDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            $e->createErrResponse();
        }

        try {
            $this->taskModel->assignTaskToUser($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return new Response([Headers::TYPE_JSON], 200);
    }

    public function dischargeTaskFromUser(array $data): Response
    {
        try {
            $dto = AssignAndDischargeTaskDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        $this->taskModel->dischargeUserFromTask($dto);
        return new Response([Headers::TYPE_JSON], 200, null);
    }

    public function updateTaskStatus(array $data): Response
    {
        try {
            $dto = UpdateTaskDTO::fromArray($data);
        } catch (TypeMismatchException $e) {
            $e->createErrResponse();
        }

        try {
            $this->taskModel->updateTaskStatus($dto->toArray());
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return new Response([Headers::TYPE_JSON], 200);
    }
}