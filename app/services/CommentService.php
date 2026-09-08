<?php

namespace ghosty\taskmgr\services;

use DateTimeImmutable;
use ghosty\taskmgr\database\custom_types\ActionStatus;
use ghosty\taskmgr\database\custom_types\ResourceType;
use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\comment\CommentDTO;
use ghosty\taskmgr\dto\comment\CreateCommentDTO;
use ghosty\taskmgr\dto\comment\EditCommentDTO;
use ghosty\taskmgr\dto\comment\FindCommentByIdDTO;
use ghosty\taskmgr\dto\comment\GetTaskCommentsDTO;
use ghosty\taskmgr\dto\comment\GetUserCommentsDTO;
use ghosty\taskmgr\dto\comment\TaskCommentDTO;
use ghosty\taskmgr\dto\comment\UserCommentDTO;
use ghosty\taskmgr\dto\log\LogDTO;
use ghosty\taskmgr\exceptions\AccessingNonAuthorizedResourceException;
use ghosty\taskmgr\exceptions\AccessingNonExistentResourceException;
use ghosty\taskmgr\models\CommentModel;
use ghosty\taskmgr\models\LogModel;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\models\UserModel;

class CommentService
{
    private CommentModel $commentModel;
    private UserModel $userModel;
    private TaskModel $taskModel;
    private LogModel $logModel;

    public function __construct(CommentModel $commentModel, UserModel $userModel, TaskModel $taskModel, logModel $logModel)
    {
        $this->commentModel = $commentModel;
        $this->userModel = $userModel;
        $this->taskModel = $taskModel;
        $this->logModel = $logModel;
    }

    public function createComment(CreateCommentDTO $dto, AuthorizationContext $context): CommentDTO
    {
        $logA = LogDTO::builder()->setUserId($context->getId())->setResourceType(ResourceType::USER)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());
        $logB = LogDTO::builder()->setResourceType(ResourceType::COMMENT)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());
        $logC = LogDTO::builder()->setResourceType(ResourceType::TASK)
            ->setTimestamp(new DateTimeImmutable('now'))->setUserId($context->getId());

        try {
            if (!$this->userModel->existsById($dto->getUserId())) {
                throw new AccessingNonExistentResourceException(
                    $dto->getUserId(),
                    'users',
                    line: __LINE__
                );
            }
            if (!$this->taskModel->existsById($dto->getTaskId())) {
                throw new AccessingNonExistentResourceException(
                    $dto->getTaskId(),
                    'tasks',
                    line: __LINE__
                );
            }
            if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $dto->getTaskId()))) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }

            $created = CommentDTO::fromArray($this->commentModel->insert($dto));

            $logA->setResourceId($dto->getUserId())->setActionStatus(ActionStatus::SUCCESS)
                ->setDescription("User " . $dto->getUserId() . " commented on task " . $dto->getTaskId());

            $logC->setResourceId($dto->getTaskId())->setActionStatus(ActionStatus::SUCCESS)
                ->setDescription("Task " . $dto->getTaskId() . " got a comment by " . $dto->getUserId());

            $logB->setResourceId($created->getId())->setActionStatus(ActionStatus::SUCCESS)
                ->setDescription("Comment " . $created->getId() . " created");

            return $created;
        } catch (\Exception $e) {
            $logA->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getUserId())
                ->setActionStatus(ActionStatus::FAILURE)
                ->setDescription("User " . $dto->getUserId() . " failed to comment on task. reason: " . $e->getMessage());

            $logC->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getTaskId())->setActionStatus(ActionStatus::FAILURE)
                ->setDescription("Task " . $dto->getTaskId() . " failed to get a comment. reason: " . $e->getMessage());

            $logB->setActionStatus(ActionStatus::FAILURE)
                ->setDescription("Failed to create comment. reason: " . $e->getMessage());


            throw $e;
        } finally {
            $this->logModel->log($logB);
            $this->logModel->log($logC);
            $this->logModel->log($logA);
        }
    }

    public function deleteComment(FindCommentByIdDTO $dto, AuthorizationContext $context): void
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::COMMENT)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));
        try {
            if (!$this->commentModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException($dto->getId(), 'comments', line: __LINE__);
            }
            $isAuthor = $this->commentModel->isAuthor($context->getId(), $dto->getId());
            if (!($context->isAdmin() || $isAuthor)) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $this->commentModel->delete($dto);

            $log->setDescription(
                "Comment " . $dto->getId() . " deleted"
            )->setactionStatus(ActionStatus::SUCCESS);
        } catch (\Exception $e) {
            $log->setDescription(
                "Comment " . $dto->getId() . " failed to delete. reason: " . $e->getMessage()
            )->setactionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function editComment(EditCommentDTO $dto, AuthorizationContext $context): CommentDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::COMMENT)->setUserId($context->getId())
        ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->commentModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException(
                    $dto->getId(),
                    'comments',
                    line: __LINE__
                );
            }
            $isAuthor = $this->commentModel->isAuthor($context->getId(), $dto->getId());
            if (!($context->isAdmin() || $isAuthor)) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $affected = CommentDTO::fromArray($this->commentModel->update($dto));

            $log->setDescription("Comment " . $dto->getId() . " updated: " . json_encode($affected))
            ->setactionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getId());

            return $affected;
        } catch (\Exception $e) {
            $log->setDescription(
                "Comment " . $dto->getId() . " failed to update. reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE)->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getUserComments(GetUserCommentsDTO $dto, AuthorizationContext $context): array
    {
        $logA = LogDTO::builder()->setResourceType(ResourceType::COMMENT)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));
        $logB = LogDTO::builder()->setResourceType(ResourceType::USER)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!($context->isAdmin() || $context->getId() == $dto->getUserId())) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $comments = $this->commentModel->search($dto);
            foreach ($comments as &$comment) {
                $comment = UserCommentDTO::fromArray($comment);
            }

            $logA->setDescription(
                "Fetched user comments for user id: " . $dto->getUserId()
            )->setActionStatus(ActionStatus::SUCCESS);

            $logB->setDescription(
                "Fetched user comments for user id: " . $dto->getUserId()
            )->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getUserId());

            return $comments;
        } catch (\Exception $e) {
            $logA->setDescription(
                "Failed to fetch user comments for user id: " . $dto->getUserId() . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE);

            $logB->setDescription(
                "Failed to fetch user comments for user id: " . $dto->getUserId() . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getUserId());

            throw $e;
        } finally {
            $this->logModel->log($logA);
            $this->logModel->log($logB);
        }
    }

    public function getTaskComments(GetTaskCommentsDTO $dto, AuthorizationContext $context): array
    {
        $logA = LogDTO::builder()->setResourceType(ResourceType::COMMENT)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));
        $logB = LogDTO::builder()->setResourceType(ResourceType::TASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $dto->getTaskId()))) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $comments = $this->commentModel->search($dto);
            foreach ($comments as &$comment) {
                $comment = TaskCommentDTO::fromArray($comment);
            }

            $logA->setDescription(
                "Fetched task comments for task id: " . $dto->getTaskId()
            )->setActionStatus(ActionStatus::SUCCESS);

            $logB->setDescription(
                "Fetched task comments for task id: " . $dto->getTaskId()
            )->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getTaskId());

            return $comments;
        } catch (\Exception $e) {
            $logA->setDescription(
                "Failed to fetch task comments for task id: " . $dto->getTaskId() . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE);

            $logB->setDescription(
                "Failed to fetch task comments for task id: " . $dto->getTaskId() . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getTaskId());

            throw $e;
        } finally {
            $this->logModel->log($logA);
            $this->logModel->log($logB);
        }
    }

    public function getAllComments(AuthorizationContext $context): array
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::COMMENT)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            $comments = $this->commentModel->findAll();
            foreach ($comments as &$comment) {
                $comment = CommentDTO::fromArray($comment);
            }

            $log->setDescription(
                "Fetched all comments."
            )->setActionStatus(ActionStatus::SUCCESS);

            return $comments;
        } catch (\Exception $e) {
            $log->setDescription(
                "Failed to fetch all comments. reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getCommentById(FindCommentByIdDTO $dto, AuthorizationContext $context): CommentDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::COMMENT)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->commentModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException($dto->getId(), 'comments', line: __LINE__);
            }
            $isAuthor = $this->commentModel->isAuthor($context->getId(), $dto->getId());
            if (!($context->isAdmin() || $isAuthor)) {
                throw new AccessingNonAuthorizedResourceException(line: __LINE__);
            }
            $comment = $this->commentModel->findById($dto);

            $log->setDescription(
                "Fetched comment with id: " . $dto->getId()
            )->setActionStatus(ActionStatus::SUCCESS)->setResourceId($dto->getId());

            return CommentDTO::fromArray($comment);
        } catch (\Exception $e) {
            $log->setDescription(
                "Failed to fetch comment with id: " . $dto->getId() . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE)
                ->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId());

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

}