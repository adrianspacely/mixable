<?php

namespace AdrianBrown\Mixable\Tests\Support;

use AdrianBrown\Mixable\Mixable;

class TypedCounterMixable extends Counter
{
    use Mixable;

    public function double(): self
    {
        $this->publicCount *= 2;

        return $this;
    }

    /** @return list<int> */
    public function history(): array
    {
        return [$this->publicCount];
    }
}
