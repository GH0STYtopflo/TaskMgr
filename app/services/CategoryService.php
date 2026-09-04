<?php

namespace ghosty\taskmgr\services;

use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\category\CategoryDTO;
use ghosty\taskmgr\dto\category\CreateCategoryDTO;
use ghosty\taskmgr\dto\category\FindCategoryByIdDTO;
use ghosty\taskmgr\dto\category\SearchCategoryDTO;
use ghosty\taskmgr\dto\category\TaskCategoryDTO;
use ghosty\taskmgr\dto\category\UpdateCategoryDTO;
use ghosty\taskmgr\dto\task\FindTaskByIdDTO;
use ghosty\taskmgr\exceptions\AccessingNonAuthorizedResourceException;
use ghosty\taskmgr\exceptions\AccessingNonExistentResourceException;
use ghosty\taskmgr\exceptions\CategoryExistsException;
use ghosty\taskmgr\models\CategoryModel;
use ghosty\taskmgr\models\TaskModel;

class CategoryService
{
    private CategoryModel $categoryModel;
    private TaskModel $taskModel;

    public function __construct(CategoryModel $categoryModel, TaskModel $taskModel)
    {
        $this->categoryModel = $categoryModel;
        $this->taskModel = $taskModel;
    }

    public function createCategory(CreateCategoryDTO $dto): CategoryDTO
    {
        if ($this->categoryModel->existsByTitle($dto->getTitle())) {
            throw new CategoryExistsException(
                $dto->getTitle(),
                line: __LINE__,
            );
        }

        $created = $this->categoryModel->insert($dto);

        return CategoryDTO::fromArray($created);
    }

    public function updateCategory(UpdateCategoryDTO $dto): CategoryDTO
    {
        if (!$this->categoryModel->existsById($dto->getId())) {
            throw new AccessingNonExistentResourceException(
                $dto->getId(),
                'categories',
                line: __LINE__,
            );
        }

        if ($this->categoryModel->existsByTitle($dto->getNewTitle())) {
            throw new CategoryExistsException(
                $dto->getNewTitle(),
                line: __LINE__,
            );
        }

        $updated = $this->categoryModel->update($dto);

        return CategoryDTO::fromArray($updated);
    }

    public function deleteCategory(FindCategoryByIdDTO $dto): void
    {
        if (!$this->categoryModel->existsById($dto->getId())) {
            throw new AccessingNonExistentResourceException(
                $dto->getId(),
                'categories',
                line: __LINE__
            );
        }

        $this->categoryModel->delete($dto);
    }

    public function searchCategory(SearchCategoryDTO $dto): array
    {
        $categories = $this->categoryModel->search($dto);

        foreach ($categories as &$category) {
            $category = CategoryDTO::fromArray($category);
        }

        return $categories;
    }

    public function getAllCategories(): array
    {
        $categories = $this->categoryModel->findAll();

        foreach ($categories as &$category) {
            $category = CategoryDTO::fromArray($category);
        }

        return $categories;
    }

    public function getCategoryById(FindCategoryByIdDTO $dto): ?CategoryDTO
    {
        if (!$this->categoryModel->existsById($dto->getId())) {
            throw new AccessingNonExistentResourceException($dto->getId(), 'categories');
        }

        $category = $this->categoryModel->findById($dto);

        return CategoryDTO::fromArray($category);
    }

    public function getTaskCategories(FindTaskByIdDTO $dto, AuthorizationContext $context): array
    {
        if (!$this->taskModel->existsById($dto->getId())) {
            throw new AccessingNonExistentResourceException($dto->getId(), 'tasks', line: __LINE__);
        }

        if (!($context->isAdmin() || $this->taskModel->isUserAssignedToTask($context->getId(), $dto->getId()))) {
            throw new AccessingNonAuthorizedResourceException(line: __LINE__);
        }

        $taskCategories = $this->categoryModel->getTaskCategories($dto);

        foreach ($taskCategories as &$category) {
            $category = TaskCategoryDTO::fromArray($category);
        }

        return $taskCategories;
    }
}