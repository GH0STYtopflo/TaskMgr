<?php

namespace ghosty\taskmgr\services;

use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\task\AddAndRemoveTaskCategory;
use ghosty\taskmgr\dto\task\AssignAndDischargeTaskDTO;
use ghosty\taskmgr\dto\task\CategoryAdditionResponseDTO;
use ghosty\taskmgr\dto\task\CreateTaskDTO;
use ghosty\taskmgr\dto\task\FindTaskByIdDTO;
use ghosty\taskmgr\dto\task\SearchTaskDTO;
use ghosty\taskmgr\dto\task\TaskAssignmentResponseDTO;
use ghosty\taskmgr\dto\task\TaskDTO;
use ghosty\taskmgr\dto\task\UpdateTaskDTO;
use ghosty\taskmgr\dto\task\UpdateTaskStatusDTO;
use ghosty\taskmgr\exceptions\AccessingNonAuthorizedResourceException;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\TaskAssignmentDoesNotExistException;
use ghosty\taskmgr\exceptions\TaskCategoryDoesNotExistException;
use ghosty\taskmgr\exceptions\TaskHasActiveSubtasksException;
use ghosty\taskmgr\exceptions\UpdatingTaskStatusToSubmittedException;
use ghosty\taskmgr\models\CategoryModel;
use ghosty\taskmgr\models\SubTaskModel;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\models\UserModel;

class TaskService
{
    private TaskModel $taskModel;
    private UserModel $userModel;
    private CategoryModel $categoryModel;

    private SubtaskModel $subtaskModel;

    public function __construct(
        TaskModel     $taskModel,
        UserModel     $userModel,
        CategoryModel $categoryModel,
        SubtaskModel  $subtaskModel)
    {
        $this->taskModel = $taskModel;
        $this->userModel = $userModel;
        $this->categoryModel = $categoryModel;
        $this->subtaskModel = $subtaskModel;
    }

    public function createTask(CreateTaskDTO $dto): TaskDTO
    {
        $created = $this->taskModel->insert($dto);

        return TaskDTO::fromArray($created);
    }

    public function deleteTask(FindTaskByIdDTO $dto): void
    {
        if (!$this->taskModel->existsById($dto->getId())) {
            throw new AccessingNonExistentRecordException($dto->getId(), 'tasks', line: __LINE__);
        }

        $this->taskModel->delete($dto);
    }

    public function getTaskById(FindTaskByIdDTO $dto, AuthorizationContext $context): ?TaskDTO
    {
        $task = $this->taskModel->findById($dto);

        if (!is_null($task) && !($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $dto->getId()))) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        if (is_null($task)) {
            return null;
        }

        return TaskDTO::fromArray($task);
    }

    public function getAllTasks(): array
    {
        $tasks = $this->taskModel->findAll();

        foreach ($tasks as &$task) {
            $task = TaskDTO::fromArray($task);
        }

        return $tasks;
    }

    public function updateTask(UpdateTaskDTO $dto): TaskDTO
    {
        if (!$this->taskModel->existsById($dto->getId())) {
            throw new AccessingNonExistentRecordException(
                $dto->getId(),
                'tasks',
                line: __LINE__,
            );
        }

        $affected = $this->taskModel->update($dto);

        return TaskDTO::fromArray($affected);
    }

    public function searchByTitle(SearchTaskDTO $dto): array
    {
        $tasks = $this->taskModel->search($dto);

        foreach ($tasks as &$task) {
            $task = TaskDTO::fromArray($task);
        }

        return $tasks;
    }

    public function assignTaskToUser(AssignAndDischargeTaskDTO $dto): TaskAssignmentResponseDTO
    {
        if ($this->taskModel->existsById($dto->getTaskId())) {
            throw new AccessingNonExistentRecordException($dto->getTaskId(), 'tasks', line: __LINE__);
        }

        if ($this->userModel->existsById($dto->getUserId())) {
            throw new AccessingNonExistentRecordException($dto->getUserId(), 'users', line: __LINE__);
        }

        return TaskAssignmentResponseDTO::fromArray($this->taskModel->assignTaskToUser($dto));
    }

    public function disChargeUserFromTask(AssignAndDischargeTaskDTO $dto): void
    {
        if ($this->taskModel->assignmentExists($dto)) {
            throw new TaskAssignmentDoesNotExistException($dto->getUserId(), $dto->getTaskId(), line: __LINE__);
        }

        $this->taskModel->dischargeUserFromTask($dto);
    }

    public function updateTaskStatus(UpdateTaskStatusDTO $dto, AuthorizationContext $context): TaskDTO
    {
        if ($this->taskModel->existsById($dto->getId())) {
            throw new AccessingNonExistentRecordException($dto->getId(), 'tasks', line: __LINE__);
        }

        if (!$this->taskModel->isUserAssignedToTask($context->getId(), $dto->getId())) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        if ($dto->getStatus() == TaskStatus::SUBMITTED) {
            throw new UpdatingTaskStatusToSubmittedException(line: __LINE__);
        }

        if ($this->subtaskModel->taskHasActiveSubtask($dto->getId()) && $dto->getStatus() == TaskStatus::FINISHED) {
            new TaskHasActiveSubtasksException($dto->getId(), line: __LINE__);
        }

        $affected = $this->taskModel->updateTaskStatus($dto);

        return TaskDTO::fromArray($affected);
    }

    public function addTaskCategory(AddAndRemoveTaskCategory $dto): CategoryAdditionResponseDTO
    {
        if ($this->taskModel->existsById($dto->getTaskId())) {
            throw new AccessingNonExistentRecordException($dto->getTaskId(), 'tasks', line: __LINE__);
        }

        if ($this->categoryModel->existsById($dto->getCategoryId())) {
            throw new AccessingNonExistentRecordException($dto->getCategoryId(), 'categories', line: __LINE__);
        }

        $created = $this->taskModel->addTaskCategory($dto);

        return CategoryAdditionResponseDTO::fromArray($created);
    }

    public function removeTaskCategory(AddAndRemoveTaskCategory $dto): void
    {
        if (!$this->taskModel->taskCategoryExists($dto)) {
            throw new TaskCategoryDoesNotExistException($dto->getTaskId(), $dto->getCategoryId(), line: __LINE__);
        }

        $this->taskModel->removeTaskCategory($dto);
    }
}