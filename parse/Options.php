<?php
declare(strict_types=1);

namespace parse;

final class Options
{
    public static function isInDebugMode(): bool
    {
        return true;
    }

    public static function getHttp2Timeout(): int
    {
        return 60;
    }

    public static function getBodySizeLimit(): int
    {
        return 13107200;
    }

    public static function getHeaderSizeLimit(): int
    {
        return 32768;
    }

    public static function getConcurrentStreamLimit(): int
    {
        return 256;
    }

    public static function getAllowedMethods(): array
    {
        return ["GET", "POST", "PUT", "PATCH", "HEAD", "OPTIONS", "DELETE"];
    }

    public static function isPushEnabled(): bool
    {
        return false;
    }

    public static function logFile(): string
    {
        return "./log/http2.log";
    }
}
