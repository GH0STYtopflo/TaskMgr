<?php

namespace ghosty\taskmgr\bridge\authentication;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\dto\user\UserDTO;
use ghosty\taskmgr\exceptions\ExpiredTokenException;
use ghosty\taskmgr\exceptions\InvalidTokenException;
use UnexpectedValueException;

class JWT
{
    private int $exp;
    private const string ISS = 'ghosty.ai';
    private string $key;
    private const string ALG = 'HS256';

    /**
     * @param int $exp
     * @param string $key
     */
    public function __construct(int $exp, string $key)
    {
        $this->exp = $exp;
        $this->key = $key;
    }


    /**
     * Generates a JWT for a user.
     *
     * @param UserDTO|DTO $data The user data used to generate the token.
     * @return string The generated JWT.
     * @throws DateMalformedStringException
     */
    public function generateToken(UserDTO | DTO $data): string
    {
        $payload = [
            'sub' => $data->getId(),
            'iss' => self::ISS,
            'iat' => new DateTimeImmutable('now', new DateTimeZone('Asia/Tehran'))->getTimestamp(),
            'exp' => time() + $this->exp
        ];

        return \Firebase\JWT\JWT::encode($payload, $this->key, self::ALG);
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
    public function decodeToken(string $token): array
    {
        try {
            $decoded = \Firebase\JWT\JWT::decode($token, new Key($this->key, self::ALG));

            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new ExpiredTokenException($e, __LINE__);
        } catch (SignatureInvalidException | DomainException | UnexpectedValueException $ex) {
            throw new InvalidTokenException($ex, __LINE__);
        }
    }
}