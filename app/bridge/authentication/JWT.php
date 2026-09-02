<?php

namespace ghosty\taskmgr\bridge\authentication;

use DateTimeImmutable;
use DateTimeZone;
use Firebase\JWT\Key;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\dto\user\UserDTO;
use ghosty\taskmgr\exceptions\ExpiredTokenException;
use ghosty\taskmgr\exceptions\InvalidTokenException;

class JWT
{
    private const int EXPIRATION_TIME = 3600;
    private const string ISS = 'ghosty.ai';
    private const string KEY = 'i_am_aware_that_hardcoding_this_in_the_code_and_pushing_it_to_remote_is_wrong_but_i_dont_have_time_for_.env_files_now';
    private const string ALG = 'HS256';

    /**
     * Generates a JWT for a user.
     *
     * @param UserDTO|DTO $data The user data used to generate the token.
     * @return string The generated JWT.
     * @throws \DateMalformedStringException
     */
    public static function generateToken(UserDTO | DTO $data): string
    {
        $payload = [
            'sub' => $data->getId(),
            'iss' => self::ISS,
            'iat' => new DateTimeImmutable('now', new DateTimeZone('Asia/Tehran'))->getTimestamp(),
            'exp' => time() + self::EXPIRATION_TIME
        ];

        return \Firebase\JWT\JWT::encode($payload, self::KEY, self::ALG);
    }

    /**
     * Verifies a JWT's signature and extracts its claims.
     *
     * @param string $token The JWT to verify and decode.
     * @return array<string, mixed> The claims contained in the token.
     *
     * @throws InvalidTokenException If the token is malformed or has an invalid signature.
     * @throws ExpiredTokenException If the token has expired.
     */
    public static function decodeToken(string $token): array
    {
        try {
            $decoded = \Firebase\JWT\JWT::decode($token, new Key(self::KEY, self::ALG));

            return (array) $decoded;
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new ExpiredTokenException($e, __LINE__);
        } catch (\Firebase\JWT\SignatureInvalidException | \DomainException | \UnexpectedValueException $ex) {
            throw new InvalidTokenException($ex, __LINE__);
        }
    }
}