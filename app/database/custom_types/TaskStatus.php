<?php

namespace ghosty\taskmgr\database\custom_types;

use Stringable;

enum TaskStatus: string
{
    case SUBMITTED = 'SUBMITTED';
    case ONGOING = 'ONGOING';
    case FINISHED = 'FINISHED';
}