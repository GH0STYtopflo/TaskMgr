<?php

namespace ghosty\taskmgr\bridge;

use ghosty\taskmgr\controllers\CategoryController;
use ghosty\taskmgr\controllers\CommentController;
use ghosty\taskmgr\controllers\SubtaskController;
use ghosty\taskmgr\controllers\TaskController;
use ghosty\taskmgr\controllers\UserController;
use ghosty\taskmgr\database\Connection;
use ghosty\taskmgr\database\DBConfig;
use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\models\CategoryModel;
use ghosty\taskmgr\models\CommentModel;
use ghosty\taskmgr\models\SubTaskModel;
use ghosty\taskmgr\models\TaskModel;
use ghosty\taskmgr\models\UserModel;

class Init
{
    public static function init(): Router
    {
        // Get connection to database
        $conn = Connection::getConnection(DBConfig::readConfig());

        // Create handle
        $handle = new DBHandle($conn);

        // Create Models
        $catModel = new CategoryModel($handle);
        $comModel = new CommentModel($handle);
        $subModel = new SubTaskModel($handle);
        $taskModel = new TaskModel($handle);
        $userModel = new UserModel($handle);

        // Feed these models to controllers
        $catCtl = new CategoryController($catModel);
        $comCtl = new CommentController($comModel, $userModel, $taskModel);
        $subCtl = new SubTaskController($subModel, $taskModel);
        $taskCtl = new TaskController($taskModel, $userModel, $catModel);
        $userCtl = new UserController($userModel, $taskModel);

        // Finally return a router obj
        return new Router($catCtl, $userCtl, $taskCtl, $subCtl, $comCtl);
    }
}