<?php

namespace ghosty\taskmgr\dto\category;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;
use ghosty\taskmgr\exceptions\TypeMismatchException;

class CategoryDTO extends DTO
{
    private int $id;
    private string $title;

    public function __construct(int $id, string $title)
    {
        $this->id = $id;
        $this->title = $title;
    }

    public static function fromArray(array $data): DTO
    {
        if (!isset($data["id"])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!isset($data['title'])) {
            throw new MissingParamException('id', line: __LINE__);
        }

        if (!is_numeric($data["id"])) {
            throw new TypeMismatchException(
                'id',
                'string(' . $data["id"] . ')',
                'int',
                null, line: __LINE__
            );
        }

        return new self((int) $data["id"], $data["title"]);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}