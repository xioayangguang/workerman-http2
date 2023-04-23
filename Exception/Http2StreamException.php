<?php
declare(strict_types=1);

namespace Exception;

final class Http2StreamException extends \Exception
{
    /** @var int */
    private $streamId;

    public function __construct(string $message, int $streamId, int $code, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->streamId = $streamId;
    }

    public function getStreamId(): int
    {
        return $this->streamId;
    }
}
