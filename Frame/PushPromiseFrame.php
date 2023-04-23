<?php
declare(strict_types=1);

namespace Frame;

use Exception\InvalidFrameException;
use Parse\Frame;
use Parse\Http2Parser;

class PushPromiseFrame extends Frame implements PaddingInterface
{
    use PaddingTrait;

    protected $defined_flags = [
        Flag::END_HEADERS,
        Flag::PADDED
    ];

    protected $type = 0x05;
    protected $stream_association = self::HAS_STREAM;

    protected $promised_stream_id;

    protected $data;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->promised_stream_id = (int)($options['promised_stream_id'] ?? null);
        $this->data = $options['data'] ?? 0;
        $isPadded = $this->getFlags()->hasFlag(Flag::PADDED);
        $headerLength = $isPadded ? 5 : 4;
        $frameLength = $this->getLength();
        if ($frameLength < $headerLength) {
            throw new InvalidFrameException("Invalid frame length", Http2Parser::PROTOCOL_ERROR);
        }
        $padding = $this->getPaddingLength();
        if ($frameLength - $headerLength - $padding < 0) {
            throw new InvalidFrameException("Padding greater than length", Http2Parser::PROTOCOL_ERROR);
        }
    }

    public function serializeBody(): string
    {
        $padding_data = $this->serializePaddingData();
        $padding = '';
        if ($this->padding_length) {
            $padding = str_repeat("\0", $this->padding_length);
        }
        $data = pack('N', $this->promised_stream_id);
        return $padding_data . $data . $this->data . $padding;
    }

    /**
     * @param string $data
     * @return void
     */
    public function parseBody(string $data)
    {
        $padding_data_length = $this->parsePaddingData($data);
        if (!$unpack = @unpack('Npromised_stream_id', substr($data, $padding_data_length, $padding_data_length + 4))) {
            throw new InvalidFrameException("Invalid PUSH_PROMISE body", Http2Parser::PROTOCOL_ERROR);
        }
        $this->promised_stream_id = $unpack['promised_stream_id'];
        $this->data = substr($data, $padding_data_length + 4);
        $this->body_len = strlen($data);
        if ($this->padding_length && $this->padding_length > $this->body_len) {
            throw new InvalidFrameException("Padding greater than length", Http2Parser::PROTOCOL_ERROR);
        }
    }

    /**
     * @return int
     */
    public function getPromisedStreamId()
    {
        return $this->promised_stream_id;
    }

    /**
     * @param int $promised_stream_id
     * @return $this
     */
    public function setPromisedStreamId(int $promised_stream_id)
    {
        $this->promised_stream_id = $promised_stream_id;
        return $this;
    }

    /**
     * @return int|mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return $this
     */
    public function setData(string $data)
    {
        $this->data = $data;
        return $this;
    }
}
