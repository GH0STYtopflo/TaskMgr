<?php

namespace ghosty\taskmgr\dto\category;

use ghosty\taskmgr\dto\DTO;

class SearchCategoryDTO extends DTO
{
    private ?string $title;

    public function __construct(?string $title)
    {
        $this->title = $title;
    }


    public static function fromArray(array $data): self
    {
        if (!isset($data['title'])) {
            $data['title'] = null;
        }

        return new self($data['title']);
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}