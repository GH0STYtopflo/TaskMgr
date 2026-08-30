<?php

namespace ghosty\taskmgr\dto;
use JsonSerializable;

/**
 * Response abstraction layer
 */
class Response
{
    private array $headers;
    private int $statusCode;
    private ?string $body;

    public function __construct(array $headers, int $statusCode, ?string $body = null)
    {
        $this->headers = $headers;
        $this->statusCode = $statusCode;
        $this->body = $body;
    }

    /**
     * Abstracts the response made by the controllers so router doesn't need to know how
     * to create and structure responses
     *
     * @param int $status The status code of response
     * @param array $headers Any additional headers
     * @param JsonSerializable|array|null $obj The DTO object which will be serialized and put in the body of the http response
     * @return Response
     */
    public static function makeResponse(int $status, array $headers, null | JsonSerializable | array $obj = null): Response
    {
        return new Response($headers, $status, is_null($obj) ? null : json_encode($obj));
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }
}