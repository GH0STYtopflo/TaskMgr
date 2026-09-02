<?php

namespace ghosty\taskmgr\util\HTTP;

use ghosty\taskmgr\dto\Request;

class RequestParser
{
    private Request $request;
    private string $template;

    /**
     * @param Request $request
     * @param string $template
     */
    public function __construct(Request $request, string $template)
    {
        $this->request = $request;
        $this->template = $template;
    }

    /**
     * Returns an associative array of info provided in both request body and URI (path vars and query params)
     *
     * @return array
     */
    public function getData(): array
    {
        $bodyStr = $this->request->getBody();
        $bodyAssoc = empty($bodyStr) || trim($bodyStr) === '' ? [] : json_decode($bodyStr, true);
        $bodyAssoc = is_array($bodyAssoc) ? $bodyAssoc : [];

        $uriData = self::parseURI($this->request->getUri(), $this->template);

        return $uriData + $bodyAssoc;
    }
    /**
     * Generates an associative array of info provided in the URI. Including path variables and query parameters.
     *
     * @param string $uri URI of the request
     * @param string $template The template which we parse the URI based on. (example: api/v2/users/{user_id}/tasks?title=udiggwhatimsayin)
     * @return array An associative array of all data provided in the URI.
     */
    private static function parseURI(string $uri, string $template): array
    {
        if (!str_contains($uri, '/')) {
            return [];
        }

        $template = explode(' ', $template)[1];
        $placeholders = [];
        $queryParams = [];
        $pathVars = [];

        foreach (explode('/', $template) as $part) {
            if (str_contains($part, '{')) {
                $var_name = substr($part, 1, strlen($part) - 2);
                $placeholders[] = $var_name;
            }
        }

        foreach (explode('/', $uri) as $part) {
            if (is_numeric($part)) {
                $pathVars[] = (int) $part;
            }

            if (str_contains($part, '?')) {
                foreach(explode('&', substr($part, strpos($part, '?') + 1)) as $keyValStr) {
                    $keyVal = explode('=', $keyValStr);
                    $queryParams[$keyVal[0]] = $keyVal[1];
                }
            }
        }

        $pathVars = array_merge(
            array_fill(0, count($placeholders) - count($pathVars), null),
            $pathVars
        );

        foreach ($placeholders as $i => $placeholder) {
            $keyValPairs[$placeholder] = $pathVars[$i];
        }

        $pathVars = $keyValPairs;

        $pathVars = empty($pathVars) ? [] : $pathVars;

        return $pathVars + $queryParams;
    }

    public function getRequestMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getRequestUri(): string
    {
        return $this->request->getUri();
    }

    public function getReqRoute(): string
    {
        return $this->getRequestMethod() . ' ' . $this->getRequestUri();
    }

    public function getRequestTemplate(): string
    {
        return $this->template;
    }
    public function getRequest(): Request
    {
        return $this->request;
    }

}