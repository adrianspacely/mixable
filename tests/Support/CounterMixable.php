<?php

namespace AdrianSpacely\Mixable\Tests\Support;

use AdrianSpacely\Mixable\Mixable;

class CounterMixable extends Counter
{
    use Mixable;

    public $booted = false;

    public function bootMixable()
    {
        $this->booted = true;
    }

    public function increment()
    {
        $this->bump();

        return $this;
    }

    public function incrementTwice()
    {
        return $this->increment()->increment();
    }

    public function incrementAndCount()
    {
        $this->bump();

        return $this->publicCount;
    }

    public function incrementAndCopy()
    {
        $this->bump();

        return clone $this;
    }

    public function incrementAndCountMacroable()
    {
        $this->bump();

        return $this->macroableInstance->counts();
    }

    public function incrementInAndOutOfScope()
    {
        $this->bump();
        $this->inScope(fn () => $this->bump());

        return $this->counts();
    }

    public function whoami()
    {
        return $this->inScope(fn () => static::class);
    }

    public function wasBooted()
    {
        return $this->booted;
    }

    public function wasConstructed()
    {
        return $this->constructed;
    }
}
