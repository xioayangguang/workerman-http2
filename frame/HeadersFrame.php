<?php
declare(strict_types=1);

namespace frame;

use exception\InvalidFrameException;
use parse\Frame;
use parse\Http2Parser;

class HeadersFrame extends Frame implements PaddingInterface, PriorityInterface
{
    use PaddingTrait, PriorityTrait;

    protected $defined_flags = [
        Flag::END_STREAM,
        Flag::END_HEADERS,
        Flag::PADDED,
        Flag::PRIORITY
    ];

    protected $type = 0x01;

    protected $stream_association = self::HAS_STREAM;
    protected $data;


    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->data = $options['data'] ?? '';
        $headerLength = 0;
        $isPadded = $this->getFlags()->hasFlag(Flag::PADDED);
        $isPriority = $this->getFlags()->hasFlag(Flag::PRIORITY); //优先级权重
        if ($isPadded) { //是否填充
            $headerLength++;
        }
        if ($isPriority) {
            $headerLength += 5;
        }
        $frameLength = $this->getLength();
        if ($frameLength < $headerLength) {
            throw new InvalidFrameException("Invalid frame length", Http2Parser::PROTOCOL_ERROR);
        }
        if ($isPriority) {
            $parent = $this->getDependsOn();
            $parent &= 0x7fffffff;
            if ($parent === $this->stream_id) {
                throw new InvalidFrameException(
                    "Invalid recursive dependency for stream {$this->stream_id}",
                    Http2Parser::PROTOCOL_ERROR
                );
            }
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
        $priority_data = '';
        if ($this->flags->hasFlag(Flag::PRIORITY)) {
            $priority_data = $this->serializePriorityData();
        }
        return $padding_data . $priority_data . $this->data . $padding;
    }

    /**
     * @param string $data
     * @return void
     */
    public function parseBody(string $data)
    {
        $padding_data_length = $this->parsePaddingData($data);
        $data = substr($data, $padding_data_length);
        $priority_data_length = 0;
        if ($this->flags->hasFlag(Flag::PRIORITY)) {
            $priority_data_length = $this->parsePriorityData($data);
        }
        $this->body_len = strlen($data);
        $this->data = substr($data, $priority_data_length, strlen($data) - $this->padding_length);
        if ($this->padding_length && $this->padding_length >= $this->body_len) {
            throw new InvalidFrameException("Padding greater than length", Http2Parser::PROTOCOL_ERROR);
        }
    }

    /**
     * @param mixed|string $data
     * @return HeadersFrame
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getData()
    {
        return $this->data;
    }
}
