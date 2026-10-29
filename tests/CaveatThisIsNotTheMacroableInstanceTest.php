<?php

namespace AdrianBrown\Mixable\Tests;

use AdrianBrown\Mixable\Mixable;
use AdrianBrown\Mixable\Mixin;
use AdrianBrown\Mixable\Tests\Support\Counter;
use AdrianBrown\Mixable\Tests\Support\TestMe;
use TypeError;

test('problem: $this is an instance of the mixin meaning you are not passing the macroable elsewhere', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function passThis()
        {
            return (fn (TestMe $testMe) => $testMe)($this);
        }
    })->mix();

    expect(fn () => TestMe::new()->passThis())->toThrow(TypeError::class);
});

test('problem: $this is an instance of the mixable subclass meaning you are not passing the macroable parent instance elsewhere', function () {
    ($mixable = new class () extends Counter {
        use Mixable;

        public function passThis()
        {
            return [$this];
        }
    })->mix();
    $counter = new Counter();

    [$passed] = $counter->passThis();

    expect($passed)->not->toBe($counter);
    expect($passed)->toBeInstanceOf(get_class($mixable));
});

test('solution: for mixins you can use the `inScope` callback so that $this is an instance of the macroable', function () {
    (new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function passThis()
        {
            return $this->inScope(fn () => [(fn (TestMe $testMe) => $testMe)($this)]);
        }
    })->mix();
    $testMe = TestMe::new();

    [$passed] = $testMe->passThis();

    expect($passed)->toBe($testMe);
});

test('solution: for mixables you can use the `inScope` callback so that $this is an instance of the macroable parent', function () {
    (new class () extends Counter {
        use Mixable;

        public function passThis()
        {
            return $this->inScope(fn () => [$this]);
        }
    })->mix();
    $counter = new Counter();

    [$passed] = $counter->passThis();

    expect($passed)->toBe($counter);
});

test('solution: the mixin was not called from the mixin instance, then the return value will be the macroable instead of the mixin', function () {
    ($mixin = new class () {
        use Mixin;

        public string $macroable = TestMe::class;

        public function returnThis()
        {
            return $this;
        }
    })->mix();
    $testMe = TestMe::new();

    expect($testMe->returnThis())->toBe($testMe);
    expect($mixin->returnThis())->toBe($mixin);
});
