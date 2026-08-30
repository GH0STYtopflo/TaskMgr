<?php

namespace ghosty\taskmgr\dto\category;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;

class CreateCategoryDTO extends DTO
{
    private string $title;

    /**
     * @param string $title
     */
    public function __construct(string $title)
    {
        $this->title = $title;
    }


    public static function fromArray(array $data): DTO
    {
        if (!isset($data['title'])) {
            throw new MissingParamException('title', line: __LINE__);
        }

        return new self($data['title']);
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}