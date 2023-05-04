<?php
declare(strict_types=1);

namespace exception;

class Http2ConnectionException extends \Exception
{
    public function __construct(string $message, int $code, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
