<?php

namespace AdrianBrown\Mixable\Tests;

use AdrianBrown\Mixable\Mixable;
use AdrianBrown\Mixable\Tests\Support\Counter;
use AdrianBrown\Mixable\Tests\Support\CounterMixable;

test('problem: mixable is created without the constructor', function () {
    // If this is a problem, the the solution is do your own "in" and "out" method.
    CounterMixable::mix();

    expect((new Counter())->wasConstructed())->toBe(true);
    expect(Counter::wasConstructed())->toBe(false);
});

test('solution: you can write your own "in" and "out" methods', function () {
    (new class () extends Counter {
        use Mixable;

        protected static function newMixableInstance($parent): self
        {
            // IN: construct the mixable, then copy the parent's state into it.
            return tap(new self(), function ($mixable) use ($parent) {
                $mixable->macroableInstance = $parent;
                $mixable->pullMacroableState();
            });
        }

        public function newMacroableInstance(): Counter
        {
            // OUT: return a brand new parent instead of updating the original.
            return new Counter($this->publicCount);
        }

        public function incrementConstructed()
        {
            $this->bump();

            return $this->constructed ? $this : null;
        }
    })->mix();

    $counter = new Counter(5);
    $result = $counter->incrementConstructed();

    expect($result)->toBeInstanceOf(Counter::class);
    expect($result)->not->toBe($counter);
    expect($result->publicCount)->toBe(6);
    expect($counter->publicCount)->toBe(5);
});
