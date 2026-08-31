<?php

namespace ghosty\taskmgr\dto\task;

use ghosty\taskmgr\dto\DTO;
use ghosty\taskmgr\exceptions\MissingParamException;

class SearchTaskByTitleDTO extends DTO
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

    public function toArray(): array
    {
        $other_stuff = ['priority', 'deadline', 'created_at', 'status'];

        $other_stuff = array_fill_keys($other_stuff, null);

         return parent::toArray() + $other_stuff;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}