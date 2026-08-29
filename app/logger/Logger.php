<?php

namespace ghosty\taskmgr\logger;
use ghosty\taskmgr\exceptions\ExceptionTemplate;

class Logger
{
    private const string PATH = __DIR__ . '/../../logs/';
    public static function log(ExceptionTemplate $e): void
    {
        $fileName = self::PATH . 'taskmgr_' . date('Y-m-d') . '.log';
        if (!file_exists($fileName)) {
            touch($fileName);
        }

        $stream = fopen($fileName, 'a+');

        $message = '';

        $message = match ($e->getSeverity()) {
            Severity::ERROR => "\e[0;31m[ERROR]]",
            Severity::WARNING => "\e[0;33m[WARNING]",
            Severity::INFO => "\e[0;37m[INFO]",
        };

        $message .= " - " . date('H:i:s') . ": " . $e->getMessage() . "\n";
    }
}
