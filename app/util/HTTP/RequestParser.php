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
        $template = explode(' ', $template)[1];
        $placeholders = [];
        $queryParams = [];
        $pathVars = [];
        $keyValPairs = [];


        // extract variable names into an array
        foreach (explode('/', $template) as $templatePart) {
            if (str_contains(':', $templatePart)) {
                $templatePart = explode(':', $templatePart)[0];
            }

            if (str_contains($templatePart, '{')) {
                $var_name = substr($templatePart, 1, strlen($templatePart) - 2);
                $placeholders[] = $var_name;
            }
        }

        foreach (explode('/', $uri) as $uriPart) {
            if (str_contains(':', $uriPart)) {
                $uriPart = explode(':', $uriPart)[0];
            }

            if (is_numeric($uriPart)) {
                $pathVars[] = (int) $uriPart;
            }

            if (str_contains($uriPart, '?')) {
                foreach(explode('&', substr($uriPart, strpos($uriPart, '?') + 1)) as $keyValStr) {
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