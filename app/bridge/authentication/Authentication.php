<?php

namespace ghosty\taskmgr\bridge\authentication;

use ghosty\taskmgr\database\DBHandle;
use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\user\FindUserByIdDTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentResourceException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\InvalidTokenException;
use ghosty\taskmgr\exceptions\TokenNotProvidedException;
use ghosty\taskmgr\exceptions\WrongAuthenticationMethodException;
use ghosty\taskmgr\logger\Severity;
use ghosty\taskmgr\models\UserModel;
use PDOException;

class Authentication
{
    private UserModel $userModel;
    private DBHandle $handle;
    private JWT $jwt;

    /**
     * @param UserModel $userModel
     * @param JWT $jwt
     * @param DBHandle $handle
     */
    public function __construct(UserModel $userModel, JWT $jwt, DBHandle $handle)
    {
        $this->userModel = $userModel;
        $this->jwt = $jwt;
        $this->handle = $handle;
    }

    /**
     * @param array $headers An array containing request headers. Will be used to extract the JWT token
     * @return AuthorizationContext An authentication context dto containing the claims necessary for authorization
     * checks
     *
     * @throws TokenNotProvidedException
     * @throws WrongAuthenticationMethodException
     * @throws AccessingNonExistentResourceException
     * @throws DatabaseException
 */
    public function authenticate(array $headers): AuthorizationContext
    {
        if (!isset($headers['Authorization'])) {
            throw new TokenNotProvidedException(line: __LINE__);
        }

        if (!str_contains($headers['Authorization'], 'Bearer ')) {
            throw new WrongAuthenticationMethodException(line: __LINE__);
        } else {
            $token = $headers['Authorization'];
            $token = str_replace('Bearer ', '', $token);
        }

        $data = $this->jwt->decodeToken($token);

        if ($this->isTokenBlacklisted($token)) {
            throw new InvalidTokenException(line: __LINE__);
        }

        return $this->generateAuthorizationContext($data);
    }

    private function generateAuthorizationContext(array $data): AuthorizationContext
    {
        $sub = $data['sub'];
        $data = $this->userModel->findById(FindUserByIdDTO::fromArray(['id' => $data['sub']]));

        if (is_null($data)) {
            throw new InvalidTokenException(line: __LINE__);
        }

        return AuthorizationContext::fromArray($data);
    }

    private function isTokenBlacklisted(string $token): bool
    {
        $segments = explode('.', $token);
        $str = $segments[1] . $segments[2];

        try {
            return $this->handle->preparedStatement(
                "SELECT EXISTS(SELECT 1 FROM token_black_list WHERE token = :token)",
                ['token' => $str]
            )->fetchColumn();
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), 500, Severity::WARNING, $e ,line: __LINE__);
        }
    }
}