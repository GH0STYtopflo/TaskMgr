<?php

namespace ghosty\taskmgr\services;

use DateTimeImmutable;
use ghosty\taskmgr\database\custom_types\ActionStatus;
use ghosty\taskmgr\database\custom_types\ResourceType;
use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\log\LogDTO;
use ghosty\taskmgr\dto\subtask\CreateSubtaskDTO;
use ghosty\taskmgr\dto\subtask\FindSubtaskById;
use ghosty\taskmgr\dto\subtask\GetTaskSubtask;
use ghosty\taskmgr\dto\subtask\SearchSubtaskDTO;
use ghosty\taskmgr\dto\subtask\SetSubtaskStatusDTO;
use ghosty\taskmgr\dto\subtask\SubtaskDTO;
use ghosty\taskmgr\dto\subtask\UpdateSubtaskTitleDTO;
use ghosty\taskmgr\exceptions\AccessingNonAuthorizedResourceException;
use ghosty\taskmgr\exceptions\AccessingNonExistentResourceException;
use ghosty\taskmgr\exceptions\SubtaskExistsException;
use ghosty\taskmgr\models\LogModel;
use ghosty\taskmgr\models\SubTaskModel;
use ghosty\taskmgr\models\TaskModel;

class SubtaskService
{
    private SubTaskModel $subTaskModel;
    private TaskModel $taskModel;

    private LogModel $logModel;

    public function __construct(SubTaskModel $subTaskModel, TaskModel $taskModel, LogModel $logModel)
    {
        $this->subTaskModel = $subTaskModel;
        $this->taskModel = $taskModel;
        $this->logModel = $logModel;
    }

    public function createSubtask(CreateSubtaskDTO $dto, AuthorizationContext $context): SubtaskDTO
    {
        $logA = LogDTO::builder()->setResourceType(ResourceType::SUBTASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));
        $logB = LogDTO::builder()->setResourceType(ResourceType::TASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->taskModel->existsById($dto->getTaskId())) {
                throw new AccessingNonExistentResourceException($dto->getTaskID(), 'tasks', line: __LINE__);
            }
            if ($this->subTaskModel->existsByTitleForTask($dto->getTitle(), $dto->getTaskId())) {
                throw new SubtaskExistsException($dto->getTitle(), line: __LINE__);
            }
            $created = SubtaskDTO::fromArray($this->subTaskModel->insert($dto));

            $logA->setResourceId($created->getId())->setActionStatus(ActionStatus::SUCCESS)
            ->setDescription(
                "Created subtask " . $created->getTitle() . " for task " . $dto->getTaskId()
            );

            $logB->setResourceId($dto->getTaskId())->setActionStatus(ActionStatus::SUCCESS)
                ->setDescription(
                    "Created subtask " . $created->getTitle() . " for task " . $dto->getTaskId()
                )->setResourceId($dto->getTaskId());

            return $created;
        } catch (\Exception $e) {
            $logA->setActionStatus(ActionStatus::FAILURE)->setDescription(
                "Failed to create subtask " . $dto->getTitle() . " for task " . $dto->getTaskId() . ". reason: " . $e->getMessage()
            );

            $logB->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getTaskId())
            ->setActionStatus(ActionStatus::FAILURE)->setDescription(
                "Failed to create subtask " . $dto->getTitle() . " for task " . $dto->getTaskId() . ". reason: " . $e->getMessage()
                );

            throw $e;
        } finally {
            $this->logModel->log($logA);
            $this->logModel->log($logB);
        }
    }

    public function deleteSubtask(FindSubtaskById $dto, AuthorizationContext $context): void
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::SUBTASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->subTaskModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException($dto->getId(), 'sub_tasks', line: __LINE__);
            }

            $this->subTaskModel->delete($dto);

            $log->setDescription(
                "Deleted subtask with id " . $dto->getId()
            )->setResourceType(ResourceType::SUBTASK)->setUserId($dto->getId());
        } catch (\Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)->setDescription(
                "Failed to delete subtask with id " . $dto->getId(). ". reason: " . $e->getMessage()
            )->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getSubtaskById(FindSubtaskById $dto, AuthorizationContext $context): ?SubtaskDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::SUBTASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->subTaskModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException($dto->getId(), 'sub_tasks', line: __LINE__);
            }
            $taskId = $this->subTaskModel->getSubtaskTaskId($dto->getId());
            if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $taskId))) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $subtask = $this->subTaskModel->findById($dto);
            if (empty($subtask)) {
                return null;
            }
            $subtask = SubtaskDTO::fromArray($subtask);

            $log->setDescription(
                "Fethed subtask with id " . $dto->getId()
            )->setResourceId($dto->getId())->setActionStatus(ActionStatus::SUCCESS);

            return $subtask;
        } catch (\Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)->setDescription(
                "Failed to get subtask with id " . $dto->getId(). ". reason: " . $e->getMessage()
            )->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getAllSubtasks(AuthorizationContext $context): array
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::SUBTASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            $subtasks = $this->subTaskModel->findAll();
            foreach ($subtasks as &$subtask) {
                $subtask = SubtaskDTO::fromArray($subtask);
            }

            $log->setDescription(
                "Fetched all subtasks"
            )->setActionStatus(ActionStatus::SUCCESS);

            return $subtasks;
        } catch (\Exception $e) {
            $log->setDescription(
                "Failed to fetch all subtasks. reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getTaskSubtasks(GetTaskSubtask $dto, AuthorizationContext $context): array
    {
        $logA = LogDTO::builder()->setResourceType(ResourceType::SUBTASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        $logB = LogDTO::builder()->setResourceType(ResourceType::TASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->taskModel->existsById($dto->getTaskId())) {
                throw new AccessingNonExistentResourceException($dto->getTaskID(), 'tasks', line: __LINE__);
            }
            if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $dto->getTaskId()))) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $subtasks = $this->subTaskModel->search($dto);
            foreach ($subtasks as &$subtask) {
                $subtask = SubtaskDTO::fromArray($subtask);
            }

            $logA->setDescription(
                "fetched subtasks for task with task_id: " . $dto->getTaskId()
            )->setActionStatus(ActionStatus::SUCCESS);

            $logB->setDescription(
                "fetched subtasks for task with task_id: " . $dto->getTaskId()
            )->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getTaskId());

            return $subtasks;
        } catch (\Exception $e) {
            $logA->setActionStatus(ActionStatus::FAILURE)->setDescription(
                "Failed to get subtasks for task with task_id: " . $dto->getTaskId() . ". reason: " . $e->getMessage()
            );

            $logB->setDescription(
                "Failed to get subtasks for task with task_id: " . $dto->getTaskId() . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getTaskId());

            throw $e;
        } finally {
            $this->logModel->log($logA);
            $this->logModel->log($logB);
        }
    }

    public function updateSubtaskStatus(SetSubtaskStatusDTO $dto, AuthorizationContext $context): SubtaskDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::SUBTASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->subTaskModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException($dto->getId(), 'sub_tasks', line: __LINE__);
            }
            $taskId = $this->subTaskModel->getSubtaskTaskId($dto->getId());
            if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $taskId))) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $affected = SubtaskDTO::fromArray($this->subTaskModel->update($dto));

            $log->setDescription(
                "Updated subtask status with id " . $dto->getId() . " to " . $dto->isIsDone()
            )->setResourceId($dto->getId())->setActionStatus(ActionStatus::SUCCESS);

            return $affected;
        } catch (\Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)->setDescription(
                "Failed to update status title with id " . $dto->getId(). " to " . $dto->isIsDone() . ". reason: " . $e->getMessage()
            )->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function updateSubtaskTitle(UpdateSubtaskTitleDTO $dto, AuthorizationContext $context): SubtaskDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::SUBTASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->subTaskModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException($dto->getId(), 'sub_tasks', line: __LINE__);
            }
            $affected = $this->subTaskModel->update($dto);

            $log->setDescription(
                "Updated subtask title with id " . $dto->getId() . " to " . $dto->getNewTitle()
            )->setResourceId($dto->getId())->setActionStatus(ActionStatus::SUCCESS);

            return SubtaskDTO::fromArray($affected);
        } catch (\Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)->setDescription(
                "Failed to update subtask title with id " . $dto->getId(). " to " . $dto->getNewTitle() . ". reason: " . $e->getMessage()
            )->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function searchSubtasks(SearchSubtaskDTO $dto, AuthorizationContext $context): array
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::SUBTASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            $subtasks = $this->subTaskModel->search($dto);
            foreach ($subtasks as &$subtask) {
                $subtask = SubtaskDTO::fromArray($subtask);
            }

            $log->setDescription(
                "User " . $context->getId() . " queried subtasks with params: " . json_encode($dto)
            )->setActionStatus(ActionStatus::SUCCESS);

            return $subtasks;
        } catch (\Exception $e) {
            $log->setActionStatus(ActionStatus::FAILURE)->setDescription(
                "User " . $context->getId() . " failed to query subtasks with params: ". json_encode($dto). ". reason: " . $e->getMessage()
            );

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }
}