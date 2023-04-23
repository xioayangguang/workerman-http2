<?php
declare(strict_types=1);

namespace Frame;

use Exception\InvalidFrameException;
use Parse\Frame;
use Parse\Http2Parser;

class RstStreamFrame extends Frame
{
    protected $defined_flags = [];
    protected $type = 0x03;
    protected $stream_association = self::HAS_STREAM;
    protected $error_code;

    /**
     * RstStreamFrame constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->error_code = (int)($options['error_code'] ?? null);
        if ($this->getLength() != 4) {
            throw new InvalidFrameException("Invalid frame length", Http2Parser::PROTOCOL_ERROR);
        }
    }

    /**
     * @return string
     */
    public function serializeBody(): string
    {
        return pack('N', $this->error_code);
    }

    /**
     * @param string $data
     * @return void
     */
    public function parseBody(string $data)
    {
        if (strlen($data) != 4) {
            throw new InvalidFrameException("RST_STREAM must have 4 byte body", Http2Parser::PROTOCOL_ERROR);
        }
        if (!$unpack = @unpack('Nerror_code', $data)) {
            throw new InvalidFrameException("Invalid RST_STREAM body", Http2Parser::PROTOCOL_ERROR);
        }
        $this->error_code = $unpack['error_code'];
        $this->body_len = strlen($data);
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * @param int $error_code
     * @return RstStreamFrame
     */
    public function setErrorCode($error_code)
    {
        $this->error_code = $error_code;
        return $this;
    }
}
