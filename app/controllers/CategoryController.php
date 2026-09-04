<?php

namespace ghosty\taskmgr\controllers;

use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\category\CreateCategoryDTO;
use ghosty\taskmgr\dto\category\FindCategoryByIdDTO;
use ghosty\taskmgr\dto\category\SearchCategoryDTO;
use ghosty\taskmgr\dto\category\UpdateCategoryDTO;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\dto\task\FindTaskByIdDTO;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\services\CategoryService;
use ghosty\taskmgr\util\HTTP\Headers;

class CategoryController
{
    private CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Maps to: POST /categories
     *
     * @param array $data
     * @return Response
     */
    public function createCategory(array $data): Response
    {
        try {
            $dto = CreateCategoryDTO::fromArray($data);
            $response = $this->categoryService->createCategory($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: PATCH /categories/{id}
     *
     * @param array $data
     * @return Response
     */
    public function updateCategory(array $data): Response
    {
        try {
            $dto = UpdateCategoryDTO::fromArray($data);
            $response = $this->categoryService->updateCategory($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: DELETE /categories/{id}
     *
     * @param array $data
     * @return Response
     */
    public function deleteCategory(array $data): Response
    {
        try {
            $dto = FindCategoryByIdDTO::fromArray($data);
            $this->categoryService->deleteCategory($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    /**
     * Maps to: GET /categories?query
     *
     * @param array $data
     * @return Response
     */
    public function searchCategory(array $data): Response
    {
        try {
            $dto = SearchCategoryDTO::fromArray($data);
            $response = $this->categoryService->searchCategory($dto);
        } catch (MissingParamException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: GET /categories
     *
     * @return Response
     */
    public function getAllCategories(): Response
    {
        try {
            $response = $this->categoryService->getAllCategories();
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: GET /categories/{id}
     *
     * @param array $data
     * @return Response
     */
    public function getCategoryById(array $data): Response
    {
        try {
            $dto = FindCategoryByIdDTO::fromArray($data);
            $response = $this->categoryService->getCategoryById($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }

    /**
     * Maps to: GET /task/{id}/categories
     *
     * @param array $data
     * @param AuthorizationContext $context
     * @return Response
     */
    public function getTaskCategories(array $data, AuthorizationContext $context): Response
    {
        try {
            $dto = FindTaskByIdDTO::fromArray($data);
            $response = $this->categoryService->getTaskCategories($dto, $context);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $response);
    }
}
