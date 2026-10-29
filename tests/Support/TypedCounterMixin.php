<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use AdrianSpacely\Mixable\Mixin;

class TypedCounterMixin
{
    use Mixin;

    public string $macroable = Counter::class;

    public function add(int $amount): static
    {
        $this->publicCount += $amount;

        return $this;
    }

    /** @return $this */
    public function reset()
    {
        $this->publicCount = 0;

        return $this;
    }

    public function addPoints(int &$value): void
    {
        $value = $value + $this->publicCount;
    }

    public function swap(int &$left, int &$right): void
    {
        [$left, $right] = [$right, $left];
    }

    public function label(): string
    {
        return "Count: {$this->publicCount}";
    }
}
