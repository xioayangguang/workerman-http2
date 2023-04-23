<?php
declare(strict_types=1);

namespace Frame;

use Exception\InvalidFrameException;
use Parse\Http2Parser;

trait PriorityTrait
{
    protected $depends_on;
    protected $stream_weight;
    protected $exclusive;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->depends_on = (int)($options['depends_on'] ?? 0);
        $this->stream_weight = (int)($options['stream_weight'] ?? 0);
        $this->exclusive = (bool)($options['exclusive'] ?? false);
    }

    /**
     * @return bool
     */
    public function getExclusive()
    {
        return $this->exclusive;
    }

    /**
     * @param bool $exclusive
     * @return PriorityTrait
     */
    public function setExclusive(bool $exclusive)
    {
        $this->exclusive = $exclusive;
        return $this;
    }

    /**
     * @return int
     */
    public function getDependsOn()
    {
        return $this->depends_on;
    }

    /**
     * @param int $depends_on
     * @return PriorityTrait
     */
    public function setDependsOn($depends_on)
    {
        $this->depends_on = $depends_on;
        return $this;
    }

    /**
     * @return int
     */
    public function getStreamWeight()
    {
        return $this->stream_weight;
    }

    /**
     * @param int $stream_weight
     * @return PriorityTrait
     */
    public function setStreamWeight($stream_weight)
    {
        $this->stream_weight = $stream_weight;
        return $this;
    }

    protected function serializePriorityData()
    {
        return pack(
            'NC',
            $this->depends_on | ((int)($this->exclusive) << 31),
            $this->stream_weight
        );
    }

    protected function parsePriorityData(string $data): int
    {
        if ($unpack = @unpack('Ndepends_on/Cstream_weight', substr($data, 0, 5))) {
            $this->depends_on = $unpack['depends_on'];
            $this->stream_weight = $unpack['stream_weight'];
            $this->exclusive = (bool)($this->depends_on & PriorityInterface::MASK);
            $this->depends_on &= ~PriorityInterface::MASK;
            return 5;
        }
        throw new InvalidFrameException("Invalid Priority data", Http2Parser::PROTOCOL_ERROR);
    }
}
