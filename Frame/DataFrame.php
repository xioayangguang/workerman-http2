<?php
declare(strict_types=1);

namespace Frame;

use Exception\InvalidFrameException;
use Parse\Frame;
use Parse\Http2Parser;

class DataFrame extends Frame implements PaddingInterface
{
    use PaddingTrait;

    protected $defined_flags = [
        Flag::END_STREAM,
        Flag::PADDED
    ];

    protected $type = 0x0;
    protected $stream_association = self::HAS_STREAM;
    protected $data;

    /**
     * DataFrame constructor.
     * @param array $options
     * @throws InvalidFrameException
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->data = $options['data'] ?? '';
        $isPadded = $this->getFlags()->hasFlag(Flag::PADDED);
        $frameLength = $this->getLength();
        $headerLength = $isPadded ? 1 : 0;
        if ($frameLength < $headerLength) {
            throw new InvalidFrameException("Invalid frame length", Http2Parser::PROTOCOL_ERROR);
        }
    }

    /**
     * @return string
     */
    public function serializeBody(): string
    {
        $padding_data = $this->serializePaddingData();
        $padding = str_repeat("\0", $this->padding_length ?? 0);
        return $padding_data . $this->data . $padding;
    }

    /**
     * @param string $data
     * @return void
     */
    public function parseBody(string $data)
    {
        $padding_data_length = $this->parsePaddingData($data);
        $this->data = $data;
        if ($this->padding_length) {
            $this->data = substr($data, $padding_data_length, $this->padding_length * -1);
        }
        $this->body_len = strlen($data);
        if ($this->padding_length && $this->padding_length >= $this->body_len) {
            throw new InvalidFrameException("Padding greater than length", Http2Parser::PROTOCOL_ERROR);
        }
    }

    /**
     * @return int
     */
    public function flowControlledLength()
    {
        $padding_len = ($this->padding_length) ? $this->padding_length + 1 : 0;
        return strlen($this->data) + $padding_len;
    }

    /**
     * @return int
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param int $data
     * @return DataFrame
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }
}
