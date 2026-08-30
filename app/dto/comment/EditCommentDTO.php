<?php

namespace ghosty\taskmgr\dto\comment;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class EditCommentDTO extends DTO
{
    private int $id;
    private string $new_body;

    /**
     * @param int $id
     * @param string $new_body
     */
    public function __construct(int $id, string $new_body)
    {
        $this->id = $id;
        $this->new_body = $new_body;
    }


    public static function fromArray(array $data): DTO
    {
        if (!isset($data["id"])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!isset($data["new_body"])) {
            throw new MissingParamException('new_body', line: __LINE__);
        }

        if (!is_numeric($data["id"])) {
            throw new TypeMismatchException(
                'id',
                'string(' . $data["uid"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        return new self($data["id"], $data["new_body"]);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNewBody(): string
    {
        return $this->new_body;
    }
}