<?php
declare(strict_types=1);

namespace frame;

use exception\InvalidFrameException;
use exception\Http2StreamException;
use parse\Frame;
use parse\Http2Parser;

class WindowUpdateFrame extends Frame
{
    protected $defined_flags = [];
    protected $type = 0x08;
    protected $stream_association = self::EITHER_STREAM;
    protected $window_increment;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->window_increment = $options['window_increment'] ?? 0;
    }

    /**
     * @return string
     */
    public function serializeBody(): string
    {
        return pack('N', $this->window_increment & 0x7FFFFFFF);
    }

    /**
     * @param string $data
     * @return void
     */
    public function parseBody(string $data)
    {
        if (!$unpack = @unpack('Nwindow_increment', $data)) {
            throw new InvalidFrameException('Invalid WINDOW_UPDATE body', Http2Parser::PROTOCOL_ERROR);
        }
        $this->window_increment = $unpack['window_increment'];
        $this->body_len = strlen($data);
        if ($this->body_len !== 4) {
            throw new InvalidFrameException("Invalid frame length", Http2Parser::PROTOCOL_ERROR);
        }
        $windowSize = $this->getWindowIncrement();
        if ($windowSize === 0) {
            if ($this->getStreamId()) {
                throw new Http2StreamException("Invalid zero window update value", $this->getStreamId(), Http2Parser::PROTOCOL_ERROR);
            }
            throw new InvalidFrameException("Invalid zero window update value", Http2Parser::PROTOCOL_ERROR);
        }
    }

    /**
     * @param int|mixed $window_increment
     * @return WindowUpdateFrame
     */
    public function setWindowIncrement($window_increment)
    {
        $this->window_increment = $window_increment;
        return $this;
    }

    /**
     * @return int|mixed
     */
    public function getWindowIncrement()
    {
        return $this->window_increment;
    }
}
