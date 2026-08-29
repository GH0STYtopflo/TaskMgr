<?php

namespace ghosty\taskmgr\util\HTTP;

enum Headers: string
{
    case TYPE_JSON = 'Content-Type: application/json';
    case TYPE_CSV = 'Content-Type: text/csv';
    case TYPE_TEXT = 'Content-Type: text/plain';
    case TYPE_HTML = 'Content-Type: text/html';
}