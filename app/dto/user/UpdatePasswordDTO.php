<?php

namespace ghosty\taskmgr\dto\user;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class UpdatePasswordDTO extends DTO
{
    private int $id;
    private string $new_password;

    /**
     * @param int $id
     * @param string $new_password
     */
    public function __construct(int $id, string $new_password)
    {
        $this->id = $id;
        $this->new_password = $new_password;
    }


    public static function fromArray(array $data): DTO
    {
        if (!isset($data["id"])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!isset($data["new_password"])) {
            throw new MissingParamException('new_password', line: __LINE__);
        }

        if (!is_numeric($data["id"])) {
            throw new TypeMismatchException(
                'id',
                'string(' . $data["id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        return new self((int) $data["id"], $data["new_password"]);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNewPassword(): string
    {
        return $this->new_password;
    }
}