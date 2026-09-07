<?php

namespace ghosty\taskmgr\database\custom_types;
enum ActionStatus: string
{
    case SUCCESS = 'SUCCESS';
    case FAILURE = 'FAILURE';
    case NA = 'NA';
}
