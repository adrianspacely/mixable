<?php

namespace AdrianBrown\Mixable\Tests\Support;

use AdrianBrown\Mixable\Mixin;

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

    public function label(): string
    {
        return "Count: {$this->publicCount}";
    }
}
