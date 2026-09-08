<?php

namespace ghosty\taskmgr\services;

use DateTimeImmutable;
use ghosty\taskmgr\database\custom_types\ActionStatus;
use ghosty\taskmgr\database\custom_types\ResourceType;
use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\category\CategoryDTO;
use ghosty\taskmgr\dto\category\CreateCategoryDTO;
use ghosty\taskmgr\dto\category\FindCategoryByIdDTO;
use ghosty\taskmgr\dto\category\SearchCategoryDTO;
use ghosty\taskmgr\dto\category\TaskCategoryDTO;
use ghosty\taskmgr\dto\category\UpdateCategoryDTO;
use ghosty\taskmgr\dto\log\LogDTO;
use ghosty\taskmgr\dto\task\FindTaskByIdDTO;
use ghosty\taskmgr\exceptions\AccessingNonAuthorizedResourceException;
use ghosty\taskmgr\exceptions\AccessingNonExistentResourceException;
use ghosty\taskmgr\exceptions\CategoryExistsException;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\models\CategoryModel;
use ghosty\taskmgr\models\LogModel;
use ghosty\taskmgr\models\TaskModel;

class CategoryService
{
    private CategoryModel $categoryModel;
    private TaskModel $taskModel;
    private LogModel $logModel;

    public function __construct(CategoryModel $categoryModel, TaskModel $taskModel, LogModel $logModel)
    {
        $this->categoryModel = $categoryModel;
        $this->taskModel = $taskModel;
        $this->logModel = $logModel;
    }

    public function createCategory(CreateCategoryDTO $dto, AuthorizationContext $context): CategoryDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::CATEGORY)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if ($this->categoryModel->existsByTitle($dto->getTitle())) {
                throw new CategoryExistsException(
                    $dto->getTitle(),
                    line: __LINE__,
                );
            }
            $created = CategoryDTO::fromArray($this->categoryModel->insert($dto));

            $log->setDescription(
                "User " . $context->getId() . " created category " . $created->getTitle()
            )->setResourceId($created->getId())->setActionStatus(ActionStatus::SUCCESS);

            return $created;
        } catch (ExceptionTemplate $e) {
            $log->setDescription(
                "User " . $context->getId() . " failed to create category " . $dto->getTitle() . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE);
            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function updateCategory(UpdateCategoryDTO $dto, AuthorizationContext $context): CategoryDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::CATEGORY)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
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

            $log->setDescription("User " . $context->getId() . " updated category " . $dto->getId() . " to " . $dto->getNewTitle())
            ->setResourceId($dto->getId())->setActionStatus(ActionStatus::SUCCESS);

            return CategoryDTO::fromArray($updated);
        } catch (ExceptionTemplate $e) {
            $log->setDescription(
                "User " . $context->getId() . " failed to update category " . $dto->getId() . ". reason: " . $e->getMessage()
            )->setResourceId($dto->getId())->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function deleteCategory(FindCategoryByIdDTO $dto, AuthorizationContext $context): void
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::CATEGORY)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->categoryModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException(
                    $dto->getId(),
                    'categories',
                    line: __LINE__
                );
            }
            $this->categoryModel->delete($dto);

            $log->setDescription("User " . $context->getId() . " deleted category " . $dto->getId())
            ->setResourceId($dto->getId())->setActionStatus(ActionStatus::SUCCESS);
        } catch (\Exception $e) {
            $log->setDescription(
                "User " . $context->getId() . " failed to delete category " . $dto->getId() . ". reason: " . $e->getMessage()
            )->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId())
                ->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function searchCategory(SearchCategoryDTO $dto, AuthorizationContext $context): array
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::CATEGORY)
            ->setUserId($context->getId())->setTimestamp(new DateTimeImmutable('now'));

        try {
            $categories = $this->categoryModel->search($dto);
            foreach ($categories as &$category) {
                $category = CategoryDTO::fromArray($category);
            }

            $log->setDescription(
                "User " . $context->getId() . " queried categories with params: " . json_encode($dto)
            )->setActionStatus(ActionStatus::SUCCESS);

            return $categories;
        } catch (\Exception $e) {
            $log->setDescription(
                "User " . $context->getId() . " failed to query categories with params: " . json_encode($dto) . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getAllCategories(AuthorizationContext $context): array
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::CATEGORY)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            $categories = $this->categoryModel->findAll();
            foreach ($categories as &$category) {
                $category = CategoryDTO::fromArray($category);
            }

            $log->setDescription(
                "User " . $context->getId() . " fetched all categories"
            )->setActionStatus(ActionStatus::SUCCESS);

            return $categories;
        } catch (\Exception $e) {
            $log->setDescription(
                "User " . $context->getId() . " failed to fetch all categories" . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getCategoryById(FindCategoryByIdDTO $dto, AuthorizationContext $context): ?CategoryDTO
    {
        $log = LogDTO::builder()->setResourceType(ResourceType::CATEGORY)
            ->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
            if (!$this->categoryModel->existsById($dto->getId())) {
                throw new AccessingNonExistentResourceException($dto->getId(), 'categories');
            }
            $category = $this->categoryModel->findById($dto);

            $log->setDescription(
                "User " . $context->getId() . " queried category with id: " . $dto->getId()
            )->setResourceId($dto->getId())->setActionStatus(ActionStatus::SUCCESS);

            return CategoryDTO::fromArray($category);
        } catch (\Exception $e) {
            $log->setDescription(
                "User " . $context->getId() . " failed queried category with id: " . $dto->getId() . ". reason: " . $e->getMessage()
            )->setResourceId($e instanceof AccessingNonExistentResourceException ? null : $dto->getId())
                ->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($log);
        }
    }

    public function getTaskCategories(FindTaskByIdDTO $dto, AuthorizationContext $context): array
    {
        $logA = LogDTO::builder()->setResourceType(ResourceType::CATEGORY)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));
        $logB = LogDTO::builder()->setResourceType(ResourceType::TASK)->setUserId($context->getId())
            ->setTimestamp(new DateTimeImmutable('now'));

        try {
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

            $logA->setDescription("User " . $context->getId() . " fetched task categories for task_id: " . $dto->getId())
            ->setActionStatus(ActionStatus::SUCCESS);

            $logB->setDescription("User " . $context->getId() . " fetched task categories for task_id: " . $dto->getId())
            ->setResourceId($dto->getId())->setActionStatus(ActionStatus::SUCCESS);

            return $taskCategories;
        } catch (\Exception $e) {
            $logA->setDescription(
                "User " . $context->getId() . " failed to fetch task categories for task_id: " . $dto->getId() . ". reason: " . $e->getMessage()
            )->setActionStatus(ActionStatus::FAILURE);

            $logB->setDescription("User " . $context->getId() . " failed to fetch task categories for task_id: " . $dto->getId() . ". reason: " . $e->getMessage())
            ->setResourceId($dto->getId())->setActionStatus(ActionStatus::FAILURE);

            throw $e;
        } finally {
            $this->logModel->log($logA);
            $this->logModel->log($logB);
        }
    }
}