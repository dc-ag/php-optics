<?php
declare(strict_types=1);

namespace dcAg\phpOptics\tests\fixtures;

class ExampleInnerData
{
    public function __construct(
        public readonly int $someInt,
        public readonly bool $someBool,
        public readonly string $someString,
    ){}

    public function equals(ExampleInnerData $other): bool
    {
        return $this->someInt === $other->someInt
            && $this->someBool === $other->someBool
            && $this->someString === $other->someString;
    }
}