<?php

namespace ghosty\taskmgr\bridge;

use ghosty\taskmgr\bridge\authentication\Authentication;
use ghosty\taskmgr\bridge\authentication\JWT;
use ghosty\taskmgr\controllers\CategoryController;
use ghosty\taskmgr\controllers\CommentController;
use ghosty\taskmgr\controllers\SubtaskController;
use ghosty\taskmgr\controllers\TaskController;
use ghosty\taskmgr\controllers\UserController;
use ghosty\taskmgr\database\Connection;
use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\models\CategoryModel;
use ghosty\taskmgr\models\CommentModel;
use ghosty\taskmgr\models\LogModel;
use ghosty\taskmgr\models\SubTaskModel;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\models\UserModel;
use ghosty\taskmgr\services\CategoryService;
use ghosty\taskmgr\services\CommentService;
use ghosty\taskmgr\services\SubtaskService;
use ghosty\taskmgr\services\TaskService;
use ghosty\taskmgr\services\UserService;

class Init
{
    public static function init(): Router
    {
        // Get connection to database (env read from dotenv)
        try {
            $conn = Connection::getConnection(
                'pgsql',
                'localhost',
                '5432',
                'taskmgr',
                'postgres',
                'postgres',
                [3 => 2, 19 => 2, 20 => false]
            );
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
        }

        // Create handle
        $handle = new DBHandle($conn);

        // Create Models
        $catModel = new CategoryModel($handle);
        $comModel = new CommentModel($handle);
        $subModel = new SubTaskModel($handle);
        $taskModel = new TaskModel($handle);
        $userModel = new UserModel($handle);
        $logModel = new LogModel($handle);

        // JWT handle
        $jwt = new JWT(200000, 'random_secret_that_will_be_read_from_dotenv_file_just_making_this_long_enough');

        // Create Services
        $srvCategory = new CategoryService($catModel, $taskModel, $logModel);
        $srvComment = new CommentService($comModel, $userModel, $taskModel, $logModel);
        $srvSubtask = new SubTaskService($subModel, $taskModel, $logModel);
        $srvTask = new TaskService($taskModel, $userModel, $catModel, $subModel, $logModel);
        $srvUser = new UserService($userModel, $taskModel, $jwt, $handle, $logModel);

        // Feed these models to controllers
        $catCtl = new CategoryController($srvCategory);
        $comCtl = new CommentController($srvComment);
        $subCtl = new SubTaskController($srvSubtask);
        $taskCtl = new TaskController($srvTask);
        $userCtl = new UserController($srvUser);

        // Auth
        $authentication = new Authentication($userModel, $jwt, $handle);

        // Finally return a router obj
        return new Router($authentication, $catCtl, $userCtl, $taskCtl, $subCtl, $comCtl);
    }
}