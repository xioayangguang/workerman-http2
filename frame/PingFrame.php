<?php
declare(strict_types=1);

namespace frame;

use exception\InvalidFrameException;
use parse\Frame;
use parse\Http2Parser;

class PingFrame extends Frame
{
    protected $defined_flags = [Flag::ACK];
    protected $type = 0x06;
    protected $stream_association = self::NO_STREAM;
    protected $opaque_data;

    /**
     * PingFrame constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->opaque_data = $options['opaque_data'] ?? '';
    }

    /**
     * @return string
     */
    public function serializeBody(): string
    {
        if (strlen($this->opaque_data) > 8) {
            throw new InvalidFrameException('PING frame may not have more than 8 bytes of data', Http2Parser::PROTOCOL_ERROR);
        }
        $data = $this->opaque_data;
        $data = str_pad($data, 8, "\x00", STR_PAD_RIGHT);
        return $data;
    }

    /**
     * @param string $data
     * @return void
     */
    public function parseBody(string $data)
    {
        if (strlen($data) != 8) {
            throw new InvalidFrameException('PING frame must have 8 byte length', Http2Parser::PROTOCOL_ERROR);
        }
        $this->opaque_data = $data;
        $this->body_len = strlen($data);
    }

    /**
     * @return string
     */
    public function getOpaqueData(): string
    {
        return $this->opaque_data;
    }

    /**
     * @param string $opaque_data
     * @return $this
     */
    public function setOpaqueData(string $opaque_data)
    {
        $this->opaque_data = $opaque_data;
        return $this;
    }
}
