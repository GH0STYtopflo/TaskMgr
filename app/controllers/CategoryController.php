<?php

namespace ghosty\taskmgr\controllers;

use ghosty\taskmgr\dto\category\CategoryDTO;
use ghosty\taskmgr\dto\category\CreateAndSearchCategoryDTO;
use ghosty\taskmgr\dto\category\FindCategoryById;
use ghosty\taskmgr\dto\category\SearchCategoryDTO;
use ghosty\taskmgr\dto\category\UpdateCategoryDTO;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\models\CategoryModel;
use ghosty\taskmgr\util\HTTP\Headers;

class CategoryController
{
    private CategoryModel $categoryModel;

    public function __construct(CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    public function createCategory(array $data): Response
    {
        try {
            $dto = CreateAndSearchCategoryDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->categoryModel->insert($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function updateCategory(array $data): Response
    {
        try {
            $dto = UpdateCategoryDTO::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->categoryModel->update($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function deleteCategory(array $data): Response
    {
        try {
            $dto = FindCategoryById::fromArray($data);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $this->categoryModel->delete($dto);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON]);
    }

    public function searchCategory(array $data): Response
    {
        try {
            $dto = SearchCategoryDTO::fromArray($data);
        } catch (MissingParamException $e) {
            return $e->createErrResponse();
        }

        try {
            $categories = $this->categoryModel->search($dto);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        foreach ($categories as &$category) {
            $category = CategoryDTO::fromArray($category);
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $categories);
    }

    public function getAllCategories(): Response
    {
        try {
            $categories = $this->categoryModel->findAll();
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        foreach ($categories as &$category) {
            $category = CategoryDTO::fromArray($category);
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], $categories);
    }

    public function getCategoryById(int $id): Response
    {
        try {
            $dto = FindCategoryById::fromArray(["id" => $id]);
        } catch (ExceptionTemplate $e) {
            return $e->createErrResponse();
        }

        try {
            $category = $this->categoryModel->findById($id);
        } catch (DatabaseException $e) {
            return $e->createErrResponse();
        }

        return Response::makeResponse(200, [Headers::TYPE_JSON], CategoryDTO::fromArray($category));
    }
}
