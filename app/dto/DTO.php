<?php

namespace ghosty\taskmgr\dto;
abstract class DTO
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
    abstract public static function fromArray(array $data): self;
}
