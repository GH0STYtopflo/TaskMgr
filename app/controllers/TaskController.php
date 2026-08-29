<?php

namespace ghosty\taskmgr\controllers;

use Exception;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\dto\task\AssignAndDischargeTaskDTO;
use ghosty\taskmgr\dto\task\CreateTaskDTO;
use ghosty\taskmgr\dto\task\UpdateTaskDTO;
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
        } catch (\Exception) {
            //TODO: LOG AND HANDLE RESPONSE
        }

        try {
            $this->taskModel->insert($dto->toArray());
        } catch (\Exception) {
            //TODO: LOG AND HANDLE RESPONSE
        }

        return new Response([Headers::TYPE_JSON], 200);
    }

    public function deleteTask(int $taskId): Response
    {
        $task = $this->taskModel->findById($taskId);

        if (is_null($task)) {
            return new Response([Headers::TYPE_JSON], 404, null);
        } else {
            $this->taskModel->delete($taskId);
            return new Response([Headers::TYPE_JSON], 204, null);
        }
    }

    public function getTaskById(int $taskId): Response
    {
        $task = $this->taskModel->findById($taskId);

        if (is_null($task)) {
            return new Response([Headers::TYPE_JSON], 404, null);
        } else {
            return new Response([Headers::TYPE_JSON], 200, json_encode($task));
        }
    }

    public function getAllTasks(): Response
    {
        $tasks = $this->taskModel->findAll();

        return new Response([Headers::TYPE_JSON], 200, json_encode($tasks));
    }

    public function updateTask(int $taskId, array $taskData): Response
    {
        $task = $this->taskModel->findById($taskId);

        if (is_null($task)) {
            return new Response([Headers::TYPE_JSON], 404, null);
        }

        try {
            $dto = UpdateTaskDTO::fromArray($taskData);
        } catch (Exception) {
            return new Response([Headers::TYPE_JSON], 400, null);
        }

        $this->taskModel->update($taskId, $dto->toArray());
        return new Response([Headers::TYPE_JSON], 204, null);
    }

    public function searchTasksByTitle(string $title): Response
    {
        $tasks = $this->taskModel->search(['title' => $title]);

        return new Response([Headers::TYPE_JSON], 200, json_encode($tasks));
    }

    public function assignTaskToUser(array $data): Response
    {
        try {
            $dto = AssignAndDischargeTaskDTO::fromArray($data);
        } catch (Exception) {
            return new Response([Headers::TYPE_JSON], 400, null);
        }

        $this->taskModel->assignTaskToUser($dto);
        return new Response([Headers::TYPE_JSON], 200, null);
    }

    public function dischargeTaskFromUser(array $data): Response
    {
        try {
            $dto = AssignAndDischargeTaskDTO::fromArray($data);
        } catch (Exception) {
            return new Response([Headers::TYPE_JSON], 400, null);
        }

        $this->taskModel->dischargeUserFromTask($dto);
        return new Response([Headers::TYPE_JSON], 200, null);
    }

    public function updateTaskStatus(array $data): Response
    {
        try {
            $dto = UpdateTaskDTO::fromArray($data);
        } catch (Exception) {
            // TODO: THROW EXCEPTION
        }

        try {
            $this->taskModel->updateTaskStatus($dto->toArray());
        } catch (Exception) {
            return new Response([Headers::TYPE_JSON], 400, null);
        }
    }
}