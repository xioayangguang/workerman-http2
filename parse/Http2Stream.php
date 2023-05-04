<?php
declare(strict_types=1);

namespace parse;

final class Http2Stream
{
    public const OPEN = 0;
    public const RESERVED = 0b0001;
    public const REMOTE_CLOSED = 0b0010;
    public const LOCAL_CLOSED = 0b0100;
    public const CLOSED = 0b0110;

    /** @var int Current max body length. */
    public $maxBodySize;

    /** 已经收到的最大数据长度  @var int Bytes received on the stream. */
    public $received = 0;

    /** 服务端数据流上面的窗口大小 @var int */
    public $serverWindow;

    /** @var int 当前流客户端还剩余的串口大小 */
    public $clientWindow;

    /** */
    public $pendingResponse;

    /** @var string */
    public $buffer = "";

    /** 客户端的流状态  @var int */
    public $state;

    /** @var int Integer between 1 and 256 */
    public $weight = 0;

    /** @var int */
    public $dependency = 0;

    /** 预期还需要接收的长度 @var int|null */
    public $expectedLength;
    /**
     * @var int
     */
    private $streamId;
    /**
     * @var Http2Driver
     */
    private $http2Driver;

    public function __construct(Http2Driver $http2Driver, int $streamId, int $serverSize, int $clientSize, int $state = self::OPEN)
    {
        $this->http2Driver = $http2Driver;
        $this->streamId = $streamId;
        $this->serverWindow = $serverSize;
        $this->maxBodySize = $serverSize;
        $this->clientWindow = $clientSize;
        $this->state = $state;
    }


    /**
     * @param string $data
     * @param bool $end 是否是最后一个流
     */
    public function sendStream(string $data, bool $end = false)
    {
        if ($end) {
            $this->state |= Http2Stream::LOCAL_CLOSED;
        }
        $this->http2Driver->writeData($data, $this->streamId);
    }
}
