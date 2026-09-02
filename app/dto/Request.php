<?php

namespace ghosty\taskmgr\dto;

class Request
{
    private string $uri;
    private string $method;
    private array $headers;
    private string $body;

    /**
     * @param string $uri
     * @param string $method
     * @param array $headers
     * @param string $body
     */
    public function __construct(string $uri, string $method, array $headers, string $body)
    {
        $this->uri = $uri;
        $this->method = $method;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}