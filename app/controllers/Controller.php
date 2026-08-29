<?php

namespace ghosty\taskmgr\controllers;

use ghosty\taskmgr\dto\Response;
use JsonSerializable;

abstract class Controller
{
    /**
     * Abstracts the response made by the controllers so router doesn't need to know how
     * to create and structure responses
     *
     * @param JsonSerializable $obj The DTO object which will be serialized and put in the body of the http response
     * @param int $status The status code of response
     * @param array $headers Any additional headers
     * @return Response
     */
    private static function makeResponse(JsonSerializable $obj, int $status, array $headers): Response
    {
        return new Response($headers, $status, json_encode($obj));
    }
}