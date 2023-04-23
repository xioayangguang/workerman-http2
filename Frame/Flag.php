<?php
declare(strict_types=1);

namespace Frame;

class Flag
{
    const END_STREAM = 0x01;
    const ACK = 0x01;
    const END_HEADERS = 0x04;
    const PADDED = 0x08;
    const PRIORITY = 0x20;

    protected $bit;

    public function __construct($bit)
    {
        $this->bit = $bit;
    }

    /**
     * @return int (as hex)
     */
    public function getBit()
    {
        return $this->bit;
    }

    public function __toString(): string
    {
        $class = new \ReflectionClass($this);
        foreach ($class->getConstants() as $name => $value) {
            if ($value == $this->bit) {
                return $name;
            }
        }
    }
}
