<?php

namespace ghosty\taskmgr\bridge\authentication;

use ghosty\taskmgr\dto\AuthorizationContext;
use ghosty\taskmgr\dto\user\FindUserByIdDTO;
use ghosty\taskmgr\exceptions\AccessingNonExistentRecordException;
use ghosty\taskmgr\exceptions\DatabaseException;
use ghosty\taskmgr\exceptions\TokenNotProvidedException;
use ghosty\taskmgr\exceptions\WrongAuthenticationMethodException;
use ghosty\taskmgr\models\UserModel;

class Authentication
{
    private UserModel $userModel;
    private JWT $jwt;

    /**
     * @param UserModel $userModel
     * @param JWT $jwt
     */
    public function __construct(UserModel $userModel, JWT $jwt)
    {
        $this->userModel = $userModel;
        $this->jwt = $jwt;
    }

    /**
     * @param array $headers An array containing request headers. Will be used to extract the JWT token
     * @return AuthorizationContext An authentication context dto containing the claims necessary for authorization
     * checks
     *
     * @throws TokenNotProvidedException
     * @throws WrongAuthenticationMethodException
     * @throws AccessingNonExistentRecordException
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

        return $this->generateAuthorizationContext($data);
    }

    private function generateAuthorizationContext(array $data): AuthorizationContext
    {
        $data = $this->userModel->findById(FindUserByIdDTO::fromArray(['id' => $data['sub']]));

        if (is_null($data)) {
            throw new AccessingNonExistentRecordException($data['sub'], 'users', line: __LINE__);
        }

        return AuthorizationContext::fromArray($data);
    }
}