<?php

namespace ghosty\taskmgr\dto\category;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class UpdateCategoryDTO extends DTO
{
    private int $id;
    private string $new_title;

    /**
     * @param int $id
     * @param string $new_title
     */
    public function __construct(int $id, string $new_title)
    {
        $this->id = $id;
        $this->new_title = $new_title;
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data["id"])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!isset($data['new_title'])) {
            throw new MissingParamException('new_title', line: __LINE__);
        }

        if (!is_numeric($data["id"])) {
            throw new TypeMismatchException(
                'id',
                'string(' . $data["id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        return new self($data["id"], $data["new_title"]);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getNewTitle(): string
    {
        return $this->new_title;
    }
}