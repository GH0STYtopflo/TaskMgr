<?php

namespace ghosty\taskmgr\services;

use ghosty\taskmgr\dto\category\CategoryDTO;
use ghosty\taskmgr\dto\category\CreateAndSearchCategoryDTO;
use ghosty\taskmgr\dto\category\FindCategoryByIdDTO;
use ghosty\taskmgr\dto\category\UpdateCategoryDTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\CategoryExistsException;
use ghosty\taskmgr\models\CategoryModel;

class CategoryService
{
    private CategoryModel $categoryModel;

    public function __construct(CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    public function createCategory(CreateAndSearchCategoryDTO $dto): CategoryDTO
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
            throw new AccessingNonExistentRecordException(
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
            throw new AccessingNonExistentRecordException(
                $dto->getId(),
                'categories',
                line: __LINE__
            );
        }

        $this->categoryModel->delete($dto);
    }

    public function searchCategory(CreateAndSearchCategoryDTO $dto): array
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
        $category = $this->categoryModel->findById($dto);

        if (empty($category)) {
            return null;
        }

        return CategoryDTO::fromArray($category);
    }
}