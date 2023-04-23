<?php
declare(strict_types=1);

namespace Frame;


use Exception\InvalidFrameException;
use Parse\Http2Parser;

class Flags implements \IteratorAggregate, \Countable
{
    protected $valid_flags = [];
    protected $flags = [];

    public function __construct(int ...$valid_flags)
    {
        $this->valid_flags = array_combine($valid_flags, $valid_flags);
    }

    public function getIterator(): array
    {
        return $this->flags;
    }

    public function add($flag)
    {
        if (isset($this->valid_flags[$flag])) {
            return $this->flags[$flag] = $flag;
        }
        $mag = sprintf("UnknownFrameError: Unknown frame type 0x%X received, length %d bytes", $flag, $this->valid_flags);
        throw new InvalidFrameException($mag, Http2Parser::PROTOCOL_ERROR);
    }

    public function remove($flag)
    {
        if (isset($this->flags[$flag])) {
            unset($this->flags[$flag]);
        }
    }

    public function hasFlag($flag)
    {
        return isset($this->flags[$flag]);
    }

    public function count()
    {
        return count($this->flags);
    }
}
