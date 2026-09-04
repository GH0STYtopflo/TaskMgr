<?php

namespace ghosty\taskmgr\controllers;

use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\comment\CreateCommentDTO;
use ghosty\taskmgr\dto\comment\EditCommentDTO;
use ghosty\taskmgr\dto\comment\FindCommentByIdDTO;
use ghosty\taskmgr\dto\comment\GetTaskCommentsDTO;
use ghosty\taskmgr\dto\comment\GetUserCommentsDTO;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\services\CommentService;
use ghosty\taskmgr\util\HTTP\Headers;

class CommentController
{
    private CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Maps to: POST /comments
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function createComment(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = CreateCommentDTO::fromArray($data);
            $response = $this->commentService->createComment($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            201,
            [Headers::TYPE_JSON],
            $response
        );
    }

    /**
     * Maps to: DELETE /comments/{id}
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function deleteComment(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindCommentByIdDTO::fromArray($data);
            $this->commentService->deleteComment($dto, $context);
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
     * @param AuthorizationContext $context
     * @return Response
     */
    public function editComment(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = EditCommentDTO::fromArray($data);
            $response = $this->commentService->editComment($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON],
            $response
        );
    }

    /**
     * Maps to: GET /users/{user_id}/comments
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function getUserComments(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = GetUserCommentsDTO::fromArray($data);
            $response = $this->commentService->getUserComments($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON],
            $response
        );
    }

    /**
     * Maps to: GET /tasks/{task_id}/comments
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function getTaskComments(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = GetTaskCommentsDTO::fromArray($data);
            $response = $this->commentService->getTaskComments($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON],
            $response
        );
    }

    /**
     * Maps to: GET /comments
     *
     * @return Response
     */
    public function getAllComments(): Response
    {
        try {
            $response = $this->commentService->getAllComments();
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON],
            $response
        );
    }

    /**
     * Maps to: GET /comments/{id}
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function getCommentById(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindCommentByIdDTO::fromArray($data);
            $response = $this->commentService->getCommentById($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(
            200,
            [Headers::TYPE_JSON],
            $response
        );
    }
}