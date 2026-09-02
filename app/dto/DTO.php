<?php

namespace ghosty\taskmgr\dto;
use JsonSerializable;
use ReflectionObject;

abstract class DTO implements JsonSerializable
{
    public function toArray(): array
    {
        $reflection = new ReflectionObject($this);
        $vars = [];

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $vars[$property->getName()] = $property->getValue($this);
            $property->setAccessible(false);
        }

        return $vars;
    }
    abstract public static function fromArray(array $data): self;

    public function jsonSerialize(): array
    {
        return self::toArray();
    }
}
