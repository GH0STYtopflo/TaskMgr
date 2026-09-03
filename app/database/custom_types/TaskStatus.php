<?php

namespace ghosty\taskmgr\database\custom_types;

enum TaskStatus: string
{
    case SUBMITTED = 'SUBMITTED';
    case ONGOING = 'ONGOING';
    case FINISHED = 'FINISHED';
}