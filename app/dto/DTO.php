<?php

namespace ghosty\taskmgr\dto;
use ghosty\taskmgr\database\custom_types\TaskStatus;
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
        $array = self::toArray();

        foreach ($array as &$value) {
            if ($value instanceof \DateTimeImmutable) {
                $value = $value->format(DATE_ATOM);
            }

            if ($value instanceof TaskStatus) {
                $value = $value->value;
            }
        }

        return $array;
    }
}
