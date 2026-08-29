<?php

namespace ghosty\taskmgr\dto;
use JsonSerializable;

abstract class DTO implements JsonSerializable
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
    abstract public static function fromArray(array $data): self;

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
