<?php
declare(strict_types=1);

namespace dcAg\phpOptics\tests\fixtures;


class ExampleOuterData
{
    public function __construct(
        public readonly ExampleInnerData $innerData,
        public readonly string $someString
    ) {}

    public function equals(ExampleOuterData $other): bool
    {
        return $this->innerData->equals($other->innerData)
            && $this->someString === $other->someString;
    }
}