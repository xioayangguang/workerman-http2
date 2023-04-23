<?php
declare(strict_types=1);

namespace Frame;

use Exception\InvalidFrameException;
use Parse\Frame;
use Parse\Http2Parser;

class PriorityFrame extends Frame implements PriorityInterface
{
    use PriorityTrait;

    protected $defined_flags = [];
    protected $type = 0x02;
    protected $stream_association = self::HAS_STREAM;

    /**
     * PingFrame constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $frameLength = $this->getLength();
        if ($frameLength !== 5) {
            throw new InvalidFrameException("Invalid frame length", Http2Parser::PROTOCOL_ERROR);
        }
        $parent = $this->getDependsOn();
        if ($parent & 0x80000000) {
            $parent &= 0x7fffffff;
        }
        $streamId = $this->getStreamId();
        if ($parent === $streamId) {
            throw new InvalidFrameException("Invalid recursive dependency for stream {$streamId}", Http2Parser::PROTOCOL_ERROR);
        }
    }

    /**
     * @return string
     */
    public function serializeBody(): string
    {
        return $this->serializePriorityData();
    }

    /**
     * @param string $data
     * @return void
     */
    public function parseBody(string $data)
    {
        $this->parsePriorityData($data);
        $this->body_len = strlen($data);
    }
}
