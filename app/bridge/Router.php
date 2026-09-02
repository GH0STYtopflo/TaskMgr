<?php

namespace ghosty\taskmgr\bridge;

use ghosty\taskmgr\bridge\authentication\Authentication;
use ghosty\taskmgr\controllers\CategoryController;
use ghosty\taskmgr\controllers\CommentController;
use ghosty\taskmgr\controllers\SubtaskController;
use ghosty\taskmgr\controllers\TaskController;
use ghosty\taskmgr\controllers\UserController;
use ghosty\taskmgr\dto\Request;
use ghosty\taskmgr\dto\Response;
use ghosty\taskmgr\exceptions\ExceptionTemplate;
use ghosty\taskmgr\exceptions\RouteAccessNotAllowed;
use ghosty\taskmgr\exceptions\RouteNotFoundException;
use ghosty\taskmgr\util\HTTP\Headers;
use ghosty\taskmgr\util\HTTP\RequestParser;

class Router
{
    private array $routes;
    private Authentication $authentication;
    private CategoryController $categoryController;
    private UserController $userController;
    private TaskController $taskController;
    private SubTaskController $subTaskController;
    private CommentController $commentController;


    public function route(Request $request): Response
    {
        foreach (array_keys($this->routes) as $route) {
            if (self::matches($request->getMethod() . ' ' . $request->getUri(), $route)) {
                if ($this->routes[$route][1]) {
                    try {
                        $context = $this->authentication->authenticate($request->getHeaders());
                    } catch (ExceptionTemplate $e) {
                        return $e->createErrResponse();
                    }
                }

                if ($this->routes[$route][2] && !$context->isAdmin()) {
                    return new RouteAccessNotAllowed($route, line: __LINE__)->createErrResponse();
                }

                $parser = new RequestParser($request, $route);
                return $this->routes[$route][0]($parser->getData());
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
        Authentication $authentication,

        CategoryController $categoryController,
        UserController     $userController,
        TaskController     $taskController,
        SubtaskController  $subTaskController,
        CommentController  $commentController
    )
    {
        $this->authentication = $authentication;

        $this->categoryController = $categoryController;
        $this->userController = $userController;
        $this->taskController = $taskController;
        $this->subTaskController = $subTaskController;
        $this->commentController = $commentController;

        // scheme: 'METHOD ROUTE' => [function, must have token, must be admin]
        $this->routes = [
            // Test
            'GET /test' => [function(array $data): Response {
                return self::test($data);
            }, false, false],

            // Login
            'POST /login' => [function(array $data): Response {
                return $this->userController->login($data);
            }, false, false],

            // Signup
            'POST /signup' => [function(array $data): Response {
                return $this->userController->createUser($data);
            }, false, false],

            // Category routes
            'POST /categories' => [function(array $data): Response {
                return $this->categoryController->createCategory($data);
            }, true, true],
            'PATCH /categories/{id}' => [function(array $data): Response {
                return $this->categoryController->updateCategory($data);
            }, true, true],
            'DELETE /categories/{id}' => [function(array $data): Response {
                return $this->categoryController->deleteCategory($data);
            }, true, true],
            'GET /categories?query' => [function(array $data): Response {
                return $this->categoryController->searchCategory($data);
            }, true, false],
            'GET /categories' => [function(array $data): Response {
                return $this->categoryController->getAllCategories();
            }, true, false],
            'GET /categories/{id}' => [function(array $data): Response {
                return $this->categoryController->getCategoryById($data);
            }, true, false],

            // Comment routes
            'POST /comments' => [function(array $data): Response {
                return $this->commentController->createComment($data);
            }, true, false],
            'DELETE /comments/{id}' => [function(array $data): Response {
                return $this->commentController->deleteComment($data);
            }, true, false],
            'PATCH /comments/{id}' => [function(array $data): Response {
                return $this->commentController->editComment($data);
            }, true, false],
            'GET /users/{user_id}/comments' => [function(array $data): Response {
                return $this->commentController->getUserComments($data);
            }, true, false],
            'GET /tasks/{task_id}/comments' => [function(array $data): Response {
                return $this->commentController->getTaskComments($data);
            }, true, false],
            'GET /comments' => [function(array $data): Response {
                return $this->commentController->getAllComments($data);
            }, true, true],
            'GET /comments/{id}' => [function(array $data): Response {
                return $this->commentController->getCommentById($data);
            }, true, false],

            // Subtask routes
            'POST /tasks/{task_id}/subtasks' => [function(array $data): Response {
                return $this->subTaskController->createSubtask($data);
            }, true, true],
            'DELETE /subtasks/{id}' => [function(array $data): Response {
                return $this->subTaskController->deleteSubtask($data);
            }, true, true],
            'GET /subtasks/{id}' => [function(array $data): Response {
                return $this->subTaskController->getSubtaskById($data);
            }, true, false],
            'GET /subtasks' => [function(array $data): Response {
                return $this->subTaskController->getAllSubtasks($data);
            }, true, true],
            'GET /tasks/{task_id}/subtasks' => [function(array $data): Response {
                return $this->subTaskController->getTaskSubtasks($data);
            }, true, false],
            'PATCH /subtasks/{id}' => [function(array $data): Response {
                return $this->subTaskController->updateSubtaskStatus($data);
            }, true, false],
            'PUT /subtasks/{id}' => [function(array $data): Response {
                return $this->subTaskController->updateSubtaskTitle($data);
            }, true, true],
            'GET /subtasks?query' => [function(array $data): Response {
                return $this->subTaskController->searchSubtasks($data);
            }, true, false],

            // Task routes
            'POST /tasks' => [function(array $data): Response {
                return $this->taskController->createTask($data);
            }, true, true],
            'DELETE /tasks/{id}' => [function(array $data): Response {
                return $this->taskController->deleteTask($data);
            }, true, true],
            'GET /tasks/{id}' => [function(array $data): Response {
                return $this->taskController->getTaskById($data);
            }, true, true],
            'GET /tasks' => function(array $data): Response {
                return $this->taskController->getAllTasks();
            },
            'PATCH /tasks/{id}' => [function(array $data): Response {
                return $this->taskController->updateTask($data);
            }, true, true],
            'GET /tasks/search' => [function(array $data): Response {
                return $this->taskController->searchTasksByTitle($data);
            }, true, true],
            'POST /tasks/{task_id}/users' => [function(array $data): Response {
                return $this->taskController->assignTaskToUser($data);
            }, true, true],
            'DELETE /tasks/{task_id}/users' => [function(array $data): Response {
                return $this->taskController->dischargeTaskFromUser($data);
            }, true, true],
            'PATCH /tasks/{id}/update_status' => [function(array $data): Response {
                return $this->taskController->updateTaskStatus($data);
            }, true, false],
            'POST /tasks/{task_id}/categories' => [function(array $data): Response {
                return $this->taskController->addTaskCategory($data);
            }, true, true],
            'DELETE /tasks/{task_id}/categories' => [function(array $data): Response {
                return $this->taskController->removeTaskCategory($data);
            }, true, true],

            // User routes
            'DELETE /users/{id}' => [function(array $data): Response {
                return $this->userController->deleteUser($data);
            }, true, true],
            'GET /users/{id}' => [function(array $data): Response {
                return $this->userController->getUserById($data);
            }, true, false],
            'GET /users' => [function(array $data): Response {
                return $this->userController->getAllUsers();
            }, true, true],
            'PATCH /users/{id}/update_username' => [function(array $data): Response {
                return $this->userController->updateUsername($data);
            }, true, false],
            'PATCH /users/{id}/update_password' => [function(array $data): Response {
                return $this->userController->updatePassword($data);
            }, true, false],
            'GET /users/{id}/tasks' => [function(array $data): Response {
                return $this->userController->getUserTasks($data);
            }, true, false],
        ];
    }

    private static function test(array $data): Response
    {
        return Response::makeResponse(200, [Headers::TYPE_JSON], ['here\' your data' => 'everything seems to be fine'] + $data);
    }
}