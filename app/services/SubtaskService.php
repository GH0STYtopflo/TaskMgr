<?php

namespace ghosty\taskmgr\services;

use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\subtask\CreateSubtaskDTO;
use ghosty\taskmgr\dto\subtask\FindSubtaskById;
use ghosty\taskmgr\dto\subtask\GetTaskSubtask;
use ghosty\taskmgr\dto\subtask\SearchSubtaskDTO;
use ghosty\taskmgr\dto\subtask\SetSubtaskStatusDTO;
use ghosty\taskmgr\dto\subtask\SubtaskDTO;
use ghosty\taskmgr\dto\subtask\UpdateSubtaskTitleDTO;
use ghosty\taskmgr\exceptions\AccessingNonAuthorizedResourceException;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\SubtaskExistsException;
use ghosty\taskmgr\models\SubTaskModel;
use ghosty\taskmgr\models\TaskModel;

class SubtaskService
{
    private SubTaskModel $subTaskModel;
    private TaskModel $taskModel;

    public function __construct(SubTaskModel $subTaskModel, TaskModel $taskModel)
    {
        $this->subTaskModel = $subTaskModel;
        $this->taskModel = $taskModel;
    }

    public function createSubtask(CreateSubtaskDTO $dto): SubtaskDTO
    {
        if (!$this->taskModel->existsById($dto->getTaskId())) {
            new AccessingNonExistentRecordException($dto->getTaskID(), 'tasks', line: __LINE__)->createErrResponse();
        }

        if ($this->subTaskModel->existsByTitleForTask($dto->getTitle(), $dto->getTaskId())) {
            throw new SubtaskExistsException($dto->getTitle(), line: __LINE__);
        }

        $created = $this->subTaskModel->insert($dto);

        return SubtaskDTO::fromArray($created);
    }

    public function deleteSubtask(FindSubtaskById $dto): void
    {
        if (!$this->subTaskModel->existsById($dto->getId())) {
            throw new AccessingNonExistentRecordException($dto->getId(), 'sub_tasks', line: __LINE__);
        }

        $this->subTaskModel->delete($dto);
    }

    public function getSubtaskById(FindSubtaskById $dto, AuthorizationContext $context): ?SubtaskDTO
    {
        if (!$this->subTaskModel->existsById($dto->getId())) {
            throw new AccessingNonExistentRecordException($dto->getId(), 'sub_tasks', line: __LINE__);
        }

        $taskId = $this->subTaskModel->getSubtaskTaskId($dto->getId());
        if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $taskId))) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $subtask = $this->subTaskModel->findById($dto);

        if (empty($subtask)) {
            return null;
        }

        return SubtaskDTO::fromArray($subtask);
    }

    public function getAllSubtasks(): array
    {
        $subtasks = $this->subTaskModel->findAll();

        foreach ($subtasks as &$subtask) {
            $subtask = SubtaskDTO::fromArray($subtask);
        }

        return $subtasks;
    }

    public function getTaskSubtasks(GetTaskSubtask $dto, AuthorizationContext $context): array
    {
        if (!$this->taskModel->existsById($dto->getTaskId())) {
            throw new AccessingNonExistentRecordException($dto->getTaskID(), 'tasks', line: __LINE__);
        }

        if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $dto->getTaskId()))) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $subtasks = $this->subTaskModel->search($dto);

        foreach ($subtasks as &$subtask) {
            $subtask = SubtaskDTO::fromArray($subtask);
        }

        return $subtasks;
    }

    public function updateSubtaskStatus(SetSubtaskStatusDTO $dto, AuthorizationContext $context): SubtaskDTO
    {
        if (!$this->subTaskModel->existsById($dto->getId())) {
            throw new AccessingNonExistentRecordException($dto->getId(), 'sub_tasks', line: __LINE__);
        }


        $taskId = $this->subTaskModel->getSubtaskTaskId($dto->getId());
        if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $taskId))) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $affected = $this->subTaskModel->update($dto);

        return SubtaskDTO::fromArray($affected);
    }

    public function updateSubtaskTitle(UpdateSubtaskTitleDTO $dto): SubtaskDTO
    {
        if (!$this->subTaskModel->existsById($dto->getId())) {
            throw new AccessingNonExistentRecordException($dto->getId(), 'sub_tasks', line: __LINE__);
        }

        $affected = $this->subTaskModel->update($dto);

        return SubtaskDTO::fromArray($affected);
    }

    public function searchSubtasks(SearchSubtaskDTO $dto): array
    {
        $subtasks = $this->subTaskModel->search($dto);

        foreach ($subtasks as &$subtask) {
            $subtask = SubtaskDTO::fromArray($subtask);
        }

        return $subtasks;
    }
}