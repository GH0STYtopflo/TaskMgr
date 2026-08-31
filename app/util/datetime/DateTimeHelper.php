<?php

namespace ghosty\taskmgr\util\datetime;

use DateTimeImmutable;
use DateTimeZone;
use ghosty\taskmgr\exceptions\MalformedDateException;

class DateTimeHelper
{
    public static function fromString(string $str): DateTimeImmutable
    {
        $datetime = DateTimeImmutable::createFromFormat(
            DateTimeFormat::DATE_TIME_FORMAT,
            $str,
            new DateTimeZone('Asia/Tehran')
        );

        if (!$datetime) {
            throw new MalformedDateException($str);
        }

        return $datetime;
    }

    public static function toString(DateTimeImmutable $datetime): string
    {
        return $datetime->format(DATE_ATOM);
    }

}