<?php


namespace ghosty\taskmgr\database\custom_types;
enum ResourceType: string
{
    case USER = 'USER';
    case TASK = 'TASK';
    case COMMENT = 'COMMENT';
    case CATEGORY = 'CATEGORY';
    case SUBTASK = 'SUBTASK';
    case NA = 'NA';
}
