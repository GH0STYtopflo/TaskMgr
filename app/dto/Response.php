<?php

namespace ghosty\taskmgr\dto;
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