<?php

namespace ghosty\taskmgr\services;

use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\comment\CommentDTO;
use ghosty\taskmgr\dto\comment\CreateCommentDTO;
use ghosty\taskmgr\dto\comment\EditCommentDTO;
use ghosty\taskmgr\dto\comment\FindCommentByIdDTO;
use ghosty\taskmgr\dto\comment\GetTaskCommentsDTO;
use ghosty\taskmgr\dto\comment\GetUserCommentsDTO;
use ghosty\taskmgr\dto\comment\TaskCommentDTO;
use ghosty\taskmgr\dto\comment\UserCommentDTO;
use ghosty\taskmgr\exceptions\AccessingNonAuthorizedResourceException;
use ghosty\taskmgr\exceptions\AccessingNonExistentResourceException;
use ghosty\taskmgr\models\CommentModel;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\models\UserModel;

class CommentService
{
    private CommentModel $commentModel;
    private UserModel $userModel;
    private TaskModel $taskModel;

    public function __construct(CommentModel $commentModel, UserModel $userModel, TaskModel $taskModel)
    {
        $this->commentModel = $commentModel;
        $this->userModel = $userModel;
        $this->taskModel = $taskModel;
    }

    public function createComment(CreateCommentDTO $dto, AuthorizationContext $context): CommentDTO
    {
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

        $created = $this->commentModel->insert($dto);

        return CommentDTO::fromArray($created);
    }

    public function deleteComment(FindCommentByIdDTO $dto, AuthorizationContext $context): void
    {
        if (!$this->commentModel->existsById($dto->getId())) {
            throw new AccessingNonExistentResourceException($dto->getId(), 'comments', line: __LINE__);
        }

        $isAuthor = $this->commentModel->isAuthor($context->getId(), $dto->getId());
        if (!($context->isAdmin() || $isAuthor)) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $this->commentModel->delete($dto);
    }

    public function editComment(EditCommentDTO $dto, AuthorizationContext $context): CommentDTO
    {
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

        $affected = $this->commentModel->update($dto);

        return CommentDTO::fromArray($affected);
    }

    public function getUserComments(GetUserCommentsDTO $dto, AuthorizationContext $context): array
    {
        if (!($context->isAdmin() || $context->getId() == $dto->getUserId())) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $comments = $this->commentModel->search($dto);

        foreach ($comments as &$comment) {
            $comment = UserCommentDTO::fromArray($comment);
        }

        return $comments;
    }

    public function getTaskComments(GetTaskCommentsDTO $dto, AuthorizationContext $context): array
    {
        if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $dto->getTaskId()))) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $comments = $this->commentModel->search($dto);

        foreach ($comments as &$comment) {
            $comment = TaskCommentDTO::fromArray($comment);
        }

        return $comments;
    }

    public function getAllComments(): array
    {
        $comments = $this->commentModel->findAll();

        foreach ($comments as &$comment) {
            $comment = CommentDTO::fromArray($comment);
        }

        return $comments;
    }

    public function getCommentById(FindCommentByIdDTO $dto, AuthorizationContext $context): CommentDTO
    {
        if (!$this->commentModel->existsById($dto->getId())) {
            throw new AccessingNonExistentResourceException($dto->getId(), 'comments', line: __LINE__);
        }

        $isAuthor = $this->commentModel->isAuthor($context->getId(), $dto->getId());
        if (!($context->isAdmin() || $isAuthor)) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $comment = $this->commentModel->findById($dto);

        return CommentDTO::fromArray($comment);
    }

}