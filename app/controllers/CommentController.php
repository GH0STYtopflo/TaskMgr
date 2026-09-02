<?php

namespace ghosty\taskmgr\controllers;

use ghosty\taskmgr\dto\comment\CommentDTO;
use ghosty\taskmgr\dto\comment\CreateCommentDTO;
use ghosty\taskmgr\dto\comment\EditCommentDTO;
use ghosty\taskmgr\dto\comment\FindCommentByIdDTO;
use ghosty\taskmgr\dto\comment\GetTaskCommentsDTO;
use ghosty\taskmgr\dto\comment\GetUserCommentsDTO;
use ghosty\taskmgr\dto\comment\TaskCommentDTO;
use ghosty\taskmgr\dto\comment\UserCommentDTO;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\models\CommentModel;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\models\UserModel;
use ghosty\taskmgr\util\HTTP\Headers;

class CommentController
{
    private CommentModel $commentModel;
    private UserModel $userModel;
    private TaskModel $taskModel;

    /**
     * @param CommentModel $commentModel
     * @param UserModel $userModel
     * @param TaskModel $taskModel
     */
    public function __construct(CommentModel $commentModel, UserModel $userModel, TaskModel $taskModel)
    {
        $this->commentModel = $commentModel;
        $this->userModel = $userModel;
        $this->taskModel = $taskModel;
    }

    /**
     * Maps to: POST /comments
     *
     * @param array $data
     * @return Response
     */
    public function createComment(array $data): Response
    {
        try {
            $dto = CreateCommentDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        if (!$this->userModel->existsById($dto->getUserId())) {
            return new AccessingNonExistentRecordException(
                $dto->getUserId(),
                'users',
                line: __LINE__
            )->createErrResponse();
        }

        if (!$this->taskModel->existsById($dto->getTaskId())) {
            return new AccessingNonExistentRecordException(
                $dto->getTaskId(),
                'tasks',
                line: __LINE__
            )->createErrResponse();
        }

        try {
            $this->commentModel->insert($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON]
        );
    }

    /**
     * Maps to: DELETE /comments/{id}
     *
     * @param array $data
     * @return Response
     */
    public function deleteComment(array $data): Response
    {
        try {
            $dto = FindCommentByIdDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->commentModel->delete($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON]
        );
    }

    /**
     * Maps to: PATCH /comments/{id}
     *
     * @param array $data
     * @return Response
     */
    public function editComment(array $data): Response
    {
        try {
            $dto = EditCommentDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->commentModel->update($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON]
        );
    }

    /**
     * Maps to: GET /users/{user_id}/comments
     *
     * @param array $data
     * @return Response
     */
    public function getUserComments(array $data): Response
    {
        try {
            $dto = GetUserCommentsDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $comments = $this->commentModel->search($dto);

            foreach ($comments as &$comment) {
                $comment = UserCommentDTO::fromArray($comment);
            }
        } catch (ExceptionTemplate $e) {
            $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON],
            $comments
        );
    }

    /**
     * Maps to: GET /tasks/{task_id}/comments
     *
     * @param array $data
     * @return Response
     */
    public function getTaskComments(array $data): Response
    {
        try {
            $dto = GetTaskCommentsDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $comments = $this->commentModel->search($dto);

            foreach ($comments as &$comment) {
                $comment = TaskCommentDTO::fromArray($comment);
            }
        } catch (ExceptionTemplate $e) {
            $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON],
            $comments
        );
    }

    /**
     * Maps to: GET /comments
     *
     * @param array $data
     * @return Response
     */
    public function getAllComments(array $data): Response
    {
        try {
            $comments = $this->commentModel->findAll();
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        foreach ($comments as &$comment) {
            $comment = CommentDTO::fromArray($comment);
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON],
            $comments
        );
    }

    /**
     * Maps to: GET /comments/{id}
     *
     * @param array $data
     * @return Response
     */
    public function getCommentById(array $data): Response
    {
        try {
            $dto = FindCommentByIdDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $comment = $this->commentModel->findById($dto->getId());
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON],
            CommentDTO::fromArray($comment)
        );
    }
}