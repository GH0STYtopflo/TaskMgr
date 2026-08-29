<?php

namespace ghosty\taskmgr\util;

class PasswordEncoder
{
    public static function encode(string $password): string
    {
        return base64_encode(password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]));
    }

    public static function matches(string $password, string $hash): bool
    {
        return password_verify($password, base64_decode($hash));
    }

}