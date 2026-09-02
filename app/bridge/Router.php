<?php

namespace ghosty\taskmgr\bridge;

use ghosty\taskmgr\controllers\CategoryController;
use ghosty\taskmgr\controllers\CommentController;
use ghosty\taskmgr\controllers\SubtaskController;
use ghosty\taskmgr\controllers\TaskController;
use ghosty\taskmgr\controllers\UserController;
use ghosty\taskmgr\dto\Request;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\exceptions\RouteNotFoundException;
use ghosty\taskmgr\util\HTTP\Headers;
use ghosty\taskmgr\util\HTTP\RequestParser;

class Router
{
    private array $routes;
    private CategoryController $categoryController;
    private UserController $userController;
    private TaskController $taskController;
    private SubTaskController $subTaskController;
    private CommentController $commentController;


    public function route(Request $request): Response
    {
        foreach (array_keys($this->routes) as $route) {
            if (self::matches($request->getMethod() . ' ' . $request->getUri(), $route)) {
                $parser = new RequestParser($request, $route);
                return $this->routes[$route]($parser->getData());
            }
        }

        return new RouteNotFoundException($request->getUri(), $request->getMethod())->createErrResponse();
    }

    private static function matches(string $reqRoute, string $template): bool
    {
        $reqMethod = explode(' ', $reqRoute)[0];
        $templateMethod = explode(' ', $template)[0];

        if ($reqMethod !== $templateMethod) {
            return false;
        }

        $reqRoute = explode(' ', $reqRoute)[1];
        $template = explode(' ', $template)[1];

        $reqRouteParts = explode('/', $reqRoute);
        $templateParts = explode('/', $template);

        $reqRouteParts = self::normalize($reqRouteParts);
        $templateParts = self::normalize($templateParts, true);

        if (count($reqRouteParts) !== count($templateParts)) {
            return false;
        }

        for ($i = 0; $i < count($templateParts); $i++) {
            if (str_contains($templateParts[$i], '{')) {
                if (!is_numeric($reqRouteParts[$i])) {
                    return false;
                }

                continue;
            }

            if (str_contains($reqRouteParts[$i], '?') xor str_contains($templateParts[$i], '?')) {
                return false;
            }

            if (!str_contains($reqRouteParts[$i], '?') && $templateParts[$i] !== $reqRouteParts[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sanitizes the uri based on the RFC standard
     * (I sanitized what I remember at the moment, but I'm sure there are more chars and sequences)
     *
     * @param array $parts
     * @param bool $isTemplate
     * @return array
     */
    private static function normalize(array $parts, bool $isTemplate = false): array
    {
        $removed = 0;
        foreach ($parts as $i => $part) {
            if (
                empty($part) ||
                (!$isTemplate &&
                ($part == '.' ||
                $part == '..' ||
                strpbrk($part, '<>"{}|\\^`') !== false ))// god bless php if this method didn't exist I had to use regex
            ) {
                array_splice($parts, $i - $removed, 1);
                $removed++;
            }
        }

        return $parts;
    }

    /**
     * @param CategoryController $categoryController
     * @param UserController $userController
     * @param TaskController $taskController
     * @param SubtaskController $subTaskController
     * @param CommentController $commentController
     */
    public function __construct(
        CategoryController $categoryController,
        UserController     $userController,
        TaskController     $taskController,
        SubtaskController  $subTaskController,
        CommentController  $commentController
    )
    {
        $this->categoryController = $categoryController;
        $this->userController = $userController;
        $this->taskController = $taskController;
        $this->subTaskController = $subTaskController;
        $this->commentController = $commentController;

        $this->routes = [
            // Test
            'GET /test' => function(array $data): Response {
                return self::test($data);
            },

            // Login
            'POST /login' => function(array $data): Response {
                return $this->userController->login($data);
            },

            // Category routes
            'POST /categories' => function(array $data): Response {
                return $this->categoryController->createCategory($data);
            },
            'PATCH /categories/{id}' => function(array $data): Response {
                return $this->categoryController->updateCategory($data);
            },
            'DELETE /categories/{id}' => function(array $data): Response {
                return $this->categoryController->deleteCategory($data);
            },
            'GET /categories?query' => function(array $data): Response {
                return $this->categoryController->searchCategory($data);
            },
            'GET /categories' => function(array $data): Response {
                return $this->categoryController->getAllCategories();
            },
            'GET /categories/{id}' => function(array $data): Response {
                return $this->categoryController->getCategoryById($data);
            },

            // Comment routes
            'POST /comments' => function(array $data): Response {
                return $this->commentController->createComment($data);
            },
            'DELETE /comments/{id}' => function(array $data): Response {
                return $this->commentController->deleteComment($data);
            },
            'PATCH /comments/{id}' => function(array $data): Response {
                return $this->commentController->editComment($data);
            },
            'GET /users/{user_id}/comments' => function(array $data): Response {
                return $this->commentController->getUserComments($data);
            },
            'GET /tasks/{task_id}/comments' => function(array $data): Response {
                return $this->commentController->getTaskComments($data);
            },
            'GET /comments' => function(array $data): Response {
                return $this->commentController->getAllComments($data);
            },
            'GET /comments/{id}' => function(array $data): Response {
                return $this->commentController->getCommentById($data);
            },

            // Subtask routes
            'POST /tasks/{task_id}/subtasks' => function(array $data): Response {
                return $this->subTaskController->createSubtask($data);
            },
            'DELETE /subtasks/{id}' => function(array $data): Response {
                return $this->subTaskController->deleteSubtask($data);
            },
            'GET /subtasks/{id}' => function(array $data): Response {
                return $this->subTaskController->getSubtaskById($data);
            },
            'GET /subtasks' => function(array $data): Response {
                return $this->subTaskController->getAllSubtasks($data);
            },
            'GET /tasks/{task_id}/subtasks' => function(array $data): Response {
                return $this->subTaskController->getTaskSubtasks($data);
            },
            'PATCH /subtasks/{id}' => function(array $data): Response {
                return $this->subTaskController->updateSubtaskStatus($data);
            },
            'PUT /subtasks/{id}' => function(array $data): Response {
                return $this->subTaskController->updateSubtaskTitle($data);
            },
            'GET /subtasks?query' => function(array $data): Response {
                return $this->subTaskController->searchSubtasks($data);
            },

            // Task routes
            'POST /tasks' => function(array $data): Response {
                return $this->taskController->createTask($data);
            },
            'DELETE /tasks/{id}' => function(array $data): Response {
                return $this->taskController->deleteTask($data);
            },
            'GET /tasks/{id}' => function(array $data): Response {
                return $this->taskController->getTaskById($data);
            },
            'GET /tasks' => function(array $data): Response {
                return $this->taskController->getAllTasks();
            },
            'PATCH /tasks/{id}' => function(array $data): Response {
                return $this->taskController->updateTask($data);
            },
            'GET /tasks/search' => function(array $data): Response {
                return $this->taskController->searchTasksByTitle($data);
            },
            'POST /tasks/{task_id}/users' => function(array $data): Response {
                return $this->taskController->assignTaskToUser($data);
            },
            'DELETE /tasks/{task_id}/users' => function(array $data): Response {
                return $this->taskController->dischargeTaskFromUser($data);
            },
            'PATCH /tasks/{id}/update-status' => function(array $data): Response {
                return $this->taskController->updateTaskStatus($data);
            },
            'POST /tasks/{task_id}/categories' => function(array $data): Response {
                return $this->taskController->addTaskCategory($data);
            },
            'DELETE /tasks/{task_id}/categories' => function(array $data): Response {
                return $this->taskController->removeTaskCategory($data);
            },

            // User routes
            'POST /signup' => function(array $data): Response {
                return $this->userController->createUser($data);
            },
            'DELETE /users/{id}' => function(array $data): Response {
                return $this->userController->deleteUser($data);
            },
            'GET /users/{id}' => function(array $data): Response {
                return $this->userController->getUserById($data);
            },
            'GET /users' => function(array $data): Response {
                return $this->userController->getAllUsers();
            },
            'PATCH /users/{id}/update_username' => function(array $data): Response {
                return $this->userController->updateUsername($data);
            },
            'PATCH /users/{id}/update_password' => function(array $data): Response {
                return $this->userController->updatePassword($data);
            },
            'GET /users/{id}/tasks' => function(array $data): Response {
                return $this->userController->getUserTasks($data);
            },
        ];
    }

    private static function test(array $data): Response
    {
        return Response::makeResponse(200, [Headers::TYPE_JSON], ['here\' your data' => 'everything seems to be fine'] + $data);
    }
}