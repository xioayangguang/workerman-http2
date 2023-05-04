<?php
declare(strict_types=1);

namespace frame;

use exception\InvalidFrameException;
use parse\Frame;
use parse\Http2Parser;

class GoAwayFrame extends Frame
{
    protected $defined_flags = [];
    protected $type = 0x07;
    protected $stream_association = self::NO_STREAM;
    protected $last_stream_id;
    protected $error_code;
    protected $additional_data;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->last_stream_id = (int)($options['last_stream_id'] ?? 0);
        $this->error_code = (int)($options['error_code'] ?? 0);
        $this->additional_data = (int)($options['additional_data'] ?? '');
    }

    public function serializeBody(): string
    {
        $data = pack('NN', $this->last_stream_id & 0x7FFFFFFF, $this->error_code);
        $data .= $this->additional_data;
        return $data;
    }

    /**
     * @param string $data
     * @return void
     */
    public function parseBody(string $data)
    {
        if (!$unpack = @unpack('Nlast_stream_id/Nerror_code', substr($data, 0, 8))) {
            throw new InvalidFrameException('Invalid GOAWAY body.', Http2Parser::PROTOCOL_ERROR);
        }
        $this->last_stream_id = $unpack['last_stream_id'];
        $this->error_code = $unpack['error_code'];
        $this->body_len = strlen($data);
        if ($this->body_len < 8) {
            throw new InvalidFrameException("Invalid frame length", Http2Parser::PROTOCOL_ERROR);
        }
        if (strlen($data) > 8) {
            $this->additional_data = substr($data, 8);
        }
    }

    /**
     * @param int $last_stream_id
     * @return GoAwayFrame
     */
    public function setLastStreamId($last_stream_id)
    {
        $this->last_stream_id = $last_stream_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastStreamId()
    {
        return $this->last_stream_id;
    }

    /**
     * @param int $error_code
     * @return GoAwayFrame
     */
    public function setErrorCode($error_code)
    {
        $this->error_code = $error_code;
        return $this;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * @param int $additional_data
     * @return GoAwayFrame
     */
    public function setAdditionalData($additional_data)
    {
        $this->additional_data = $additional_data;
        return $this;
    }

    /**
     * @return int
     */
    public function getAdditionalData()
    {
        return $this->additional_data;
    }
}
