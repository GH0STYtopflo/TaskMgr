<?php

namespace ghosty\taskmgr\services;

use DateTimeImmutable;
use ghosty\taskmgr\database\custom_types\ActionStatus;
use ghosty\taskmgr\database\custom_types\ResourceType;
use ghosty\taskmgr\database\custom_types\TaskStatus;
use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\log\LogDTO;
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
use ghosty\taskmgr\exceptions\AccessingNonExistentResourceException;
use ghosty\taskmgr\exceptions\TaskAssignmentDoesNotExistException;
use ghosty\taskmgr\exceptions\TaskCategoryDoesNotExistException;
use ghosty\taskmgr\exceptions\TaskCategoryExistsException;
use ghosty\taskmgr\exceptions\TaskHasActiveSubtasksException;
use ghosty\taskmgr\exceptions\UpdatingTaskStatusToSubmittedException;
use ghosty\taskmgr\exceptions\UserAlreadyAssignedException;
use ghosty\taskmgr\models\CategoryModel;
use ghosty\taskmgr\models\LogModel;
use ghosty\taskmgr\models\SubTaskModel;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\models\UserModel;

class TaskService
{
    private TaskModel $taskModel;
    private UserModel $userModel;
    private CategoryModel $categoryModel;

    private SubtaskModel $subtaskModel;
    private LogModel $logModel;

    public function __construct(
        TaskModel     $taskModel,
        UserModel     $userModel,
        CategoryModel $categoryModel,
        SubtaskModel  $subtaskModel,
        LogModel      $logModel
    )
    {
        $this->taskModel = $taskModel;
        $this->userModel = $userModel;
        $this->categoryModel = $categoryModel;
        $this->subtaskModel = $subtaskModel;
        $this->logModel = $logModel;
    }

    public function createTask(CreateTaskDTO $dto, AuthorizationContext $context): TaskDTO
    {
        $log = LogDTO::builder()->setUserId($context->getId())->setResourceType(ResourceType::TASK)
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            $created = TaskDTO::fromArray($this->taskModel->insert($dto));


            $log->setDescription(
                "Created task"
            )
                ->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($created->getId());

            return $created;
        } catch (\Exception $e) {
            $log->setDescription("Failed to create task. reason: " . $e->getMessage());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function deleteTask(FindTaskByIdDTO $dto, AuthorizationContext $context): void
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::TASK)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());

        try {
            if (!$this->taskModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException($dto->getId(), 'tasks', line: __LINE__);
            }
            $this->taskModel->delete($dto);

            $log->setResourceId($dto->getId())->setDescription(
                "Deleted task with id {$dto->getId()}"
            )->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getId());
        } catch (\Exception $e) {
            $log->setDescription("Failed to delete task with id {$dto->getId()}. reason: " . $e->getMessage())
            ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId())
            ->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getTaskById(FindTaskByIdDTO $dto, AuthorizationContext $context): ?TaskDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::TASK)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());

        try {
            $task = $this->taskModel->findById($dto);
            if (!$this->taskModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException($dto->getId(), 'tasks', line: __LINE__);
            }
            if (!is_null($task) && !($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $dto->getId()))) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            if (is_null($task)) {
                return null;
            }
            $task = TaskDTO::fromArray($task);

            $log->setDescription(
                "Fetched task with id {$dto->getId()}"
            )->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getId());

            return $task;
        } catch (\Exception $e) {
            $log->setDescription("Failed to get task with id {$dto->getId()}. reason: " . $e->getMessage())
            ->setActionStatus(ActionStatus::FAILURE)->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getAllTasks(AuthorizationContext $context): array
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::TASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            $tasks = $this->taskModel->findAll();
            foreach ($tasks as &$task) {
                $task = TaskDTO::fromArray($task);
            }

            $log->setDescription(
                "Fetched all tasks"
            )->setActionStatus(ActionStatus::SUCCESS);

            return $tasks;
        } catch (\Exception $e) {
            $log->setDescription("Failed to fetch tasks. reason: " . $e->getMessage())->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function updateTask(UpdateTaskDTO $dto, AuthorizationContext $context): TaskDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::TASK)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());

        try {
            if (!$this->taskModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException(
                    $dto->getId(),
                    'tasks',
                    line: __LINE__,
                );
            }
            $affected = TaskDTO::fromArray($this->taskModel->update($dto));

            $log->setDescription(
                "Updated task with id {$dto->getId()}. Update: " . json_encode($dto)
            )->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getId());

            return $affected;
        } catch (\Exception $e) {
            $log->setDescription(
                "Failed to update task with id {$dto->getId()}. Update: " . json_encode($dto) . ". reason: " . $e->getMessage()
            )
                ->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function search(SearchTaskDTO $dto, AuthorizationContext $context): array
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::TASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            $tasks = $this->taskModel->search($dto);
            foreach ($tasks as &$task) {
                $task = TaskDTO::fromArray($task);
            }

            $log->setDescription(
                "Queried tasks with params: " . json_encode($tasks)
            )->setActionStatus(ActionStatus::SUCCESS);

            return $tasks;
        } catch (\Exception $e) {
            $log->setDescription(
                "failed to query tasks with params: " . json_encode($tasks) . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function assignTaskToUser(AssignAndDischargeTaskDTO $dto): TaskAssignmentResponseDTO
    {
        $logA = LogDTO::builder()->setResourceType(ResourceType::TASK)
            ->setTimestamp(new DateTimeImmutable('now'));
        $logB = LogDTO::builder()->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->taskModel->existsById($dto->getTaskId())) {
                throw new AccessingNonExistentResourceException($dto->getTaskId(), 'tasks', line: __LINE__);
            }
            if (!$this->userModel->existsById($dto->getUserId())) {
                throw new AccessingNonExistentResourceException($dto->getUserId(), 'users', line: __LINE__);
            }
            if ($this->taskModel->isUserAssignedToTask($dto->getUserId(), $dto->getTaskId())) {
                throw new UserAlreadyAssignedException($dto->getUserId(), $dto->getTaskId());
            }

            $assignment = TaskAssignmentResponseDTO::fromArray($this->taskModel->assignTaskToUser($dto));

            $logA->setDescription(
                "Assigned user {$dto->getUserId()} to task {$dto->getTaskId()}"
            )->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getTaskId());

            $logB->setDescription("Assigned user {$dto->getUserId()} to task {$dto->getTaskId()}")
            ->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getUserId());

            return $assignment;
        } catch (\Exception $e) {
            $logA->setDescription(
                "Failed to assign user {$dto->getUserId()} to task {$dto->getTaskId()}. reason: " . json_encode($e->getMessage())
            )
                ->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getTaskId());
            $logB
                ->setDescription("Failed to assign user {$dto->getUserId()} to task {$dto->getTaskId()}. reason: " . json_encode($e->getMessage()))
                ->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getUserId());

            throw $e;
        } finally {
            $this->logModel->log($logA);
            $this->logModel->log($logB);
        }
    }

    public function disChargeUserFromTask(AssignAndDischargeTaskDTO $dto, AuthorizationContext $context): void
    {
        $logA = LogDTO::builder()->setResourceType(ResourceType::TASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));
        $logB = LogDTO::builder()->setResourceType(ResourceType::USER)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->taskModel->assignmentExists($dto)) {
                throw new TaskAssignmentDoesNotExistException($dto->getUserId(), $dto->getTaskId(), line: __LINE__);
            }
            $this->taskModel->dischargeUserFromTask($dto);

            $logA->setDescription(
                "Discharged user {$dto->getUserId()} from task {$dto->getTaskId()}"
            )->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getTaskId());

            $logB->setDescription(
                "Discharged user {$dto->getUserId()} from task {$dto->getTaskId()}"
            )->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getUserId());

        } catch (\Exception $e) {
            $logA->setDescription(
                "Failed to discharge user {$dto->getUserId()} to task {$dto->getTaskId()}. reason: "  . $e->getMessage())
                ->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getTaskId());

            $logB->setDescription(
                "Failed to discharge user {$dto->getUserId()} to task {$dto->getTaskId()}. reason: "  . $e->getMessage())
                ->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getUserId());

            throw $e;
        } finally {
            $this->logModel->log($logA);
            $this->logModel->log($logB);
        }
    }

    public function updateTaskStatus(UpdateTaskStatusDTO $dto, AuthorizationContext $context): TaskDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::TASK)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());

        try {
            if (!$this->taskModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException($dto->getId(), 'tasks', line: __LINE__);
            }
            if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $dto->getId()))) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            if ($dto->getStatus() == TaskStatus::SUBMITTED) {
                throw new UpdatingTaskStatusToSubmittedException(line: __LINE__);
            }
            if ($this->subtaskModel->taskHasActiveSubtask($dto->getId()) && $dto->getStatus() == TaskStatus::FINISHED) {
                throw new TaskHasActiveSubtasksException($dto->getId(), line: __LINE__);
            }
            $affected = TaskDTO::fromArray($this->taskModel->updateTaskStatus($dto));

            $log->setDescription(
                "Updated task status of task {$dto->getId()} to {$dto->getStatus()}"
            )
                ->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($dto->getId());

            return $affected;
        } catch (\Exception $e) {
            $log->setDescription(
                "Failed to update task status of task {$dto->getId()} to {$dto->getStatus()}. reason: "  . $e->getMessage()
            )
                ->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function addTaskCategory(AddAndRemoveTaskCategory $dto, AuthorizationContext $context): CategoryAdditionResponseDTO
    {
        $logA = LogDTO::builder()->setResourceType(ResourceType::TASK)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());
        $logB = LogDTO::builder()->setResourceType(ResourceType::CATEGORY)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());

        try {
            if (!$this->taskModel->existsById($dto->getTaskId())) {
                throw new AccessingNonExistentResourceException($dto->getTaskId(), 'tasks', line: __LINE__);
            }
            if (!$this->categoryModel->existsById($dto->getCategoryId())) {
                throw new AccessingNonExistentResourceException($dto->getCategoryId(), 'categories', line: __LINE__);
            }
            if ($this->categoryModel->taskHasCategory($dto->getTaskId(), $dto->getCategoryId())) {
                throw new TaskCategoryExistsException($dto->getTaskId(), $dto->getCategoryId(), line: __LINE__);
            }
            $created = CategoryAdditionResponseDTO::fromArray($this->taskModel->addTaskCategory($dto));

            $logA->setDescription(
                "Added task {$dto->getTaskId()} to category {$dto->getCategoryId()}"
            )
                ->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($dto->getTaskId());

            $logB->setDescription(
                "Added task {$dto->getTaskId()} to category {$dto->getCategoryId()}"
            )
                ->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($dto->getCategoryId());

            return $created;
        } catch (\Exception $e) {
            $logA->setDescription(
                "Failed to add task {$dto->getTaskId()} to category {$dto->getCategoryId()}. reason: "  . $e->getMessage()
            )
                ->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getTaskId());

            $logB->setDescription(
                "Failed to add task {$dto->getTaskId()} to category {$dto->getCategoryId()}. reason: "  . $e->getMessage()
            )
                ->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getCategoryId());

            throw $e;
        } finally {
            $this->logModel->log($logA);
            $this->logModel->log($logB);
        }
    }

    public function removeTaskCategory(AddAndRemoveTaskCategory $dto, AuthorizationContext $context): void
    {
        $logA = LogDTO::builder()->setResourceType(ResourceType::TASK)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());
        $logB = LogDTO::builder()->setResourceType(ResourceType::CATEGORY)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());

        try {
            if (!$this->taskModel->taskCategoryExists($dto)) {
                throw new TaskCategoryDoesNotExistException($dto->getTaskId(), $dto->getCategoryId(), line: __LINE__);
            }

            $this->taskModel->removeTaskCategory($dto);

            $logA->setDescription(
                "Removed task {$dto->getTaskId()} from category {$dto->getCategoryId()}"
            )
                ->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($dto->getTaskId());

            $logB->setDescription(
                "Removed task {$dto->getTaskId()} from category {$dto->getCategoryId()}"
            )
                ->setActionStatus(ActionStatus::SUCCESS)
                ->setResourceId($dto->getCategoryId());

        } catch (\Exception $e) {
            $logA->setDescription(
                "Failed to remove task {$dto->getTaskId()} from category {$dto->getCategoryId()}. reason: "  . $e->getMessage()
            )
                ->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getTaskId());

            $logB->setDescription(
                "Failed to remove task {$dto->getTaskId()} from category {$dto->getCategoryId()}. reason: "  . $e->getMessage()
            )
                ->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getCategoryId());

            throw $e;
        } finally {
            $this->logModel->log($logA);
        }
    }
}